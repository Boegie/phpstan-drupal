<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal\PluginManager;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Type;

/**
 * @implements \PHPStan\Rules\Rule<Node\Stmt\Class_>
 */
class PluginManagerSetsCacheBackendRulez extends AbstractPluginManagerRule
{

    private ReflectionProvider $reflectionProvider;

    public function __construct(
        ReflectionProvider $reflectionProvider
    ) {
        $this->reflectionProvider = $reflectionProvider;
    }

    public function getNodeType(): string
    {
        return Class_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof Class_);

        $hasCacheBackendSet = false;
        $misnamedCacheTagWarnings = [];

        foreach ($node->stmts as $statement) {
            if ($statement instanceof Node\Stmt\ClassMethod && $statement->name->name === '__construct') {
                $namespacedName = (string) $node->namespacedName;
                $scopeClassReflection = $this->reflectionProvider->getClass($namespacedName);

                if (!$this->isPluginManager($scopeClassReflection)) {
                    return [];
                }

                // Classes implementing their own setCacheBackend() don't need further checking.
                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof Node\Stmt\ClassMethod && $stmt->name->name === 'setCacheBackend') {
                        $hasCacheBackendSet = true;
                        break(2);
                    }
                }

                foreach ($statement->getStmts() ?? [] as $constructorStatement) {
                    if ($constructorStatement->expr->name->name === 'setCacheBackend') {
                        // setCacheBackend accepts a cache backend, the cache key, and optional (but suggested) cache tags.
                        $setCacheBackendArgs = $constructorStatement->expr->getArgs();
                        if (count($setCacheBackendArgs) < 2) {
                            continue;
                        }
                        $hasCacheBackendSet = true;

                        $cacheKey = array_map(
                            static fn(Type $type) => $type->getValue(),
                            $scope->getType($setCacheBackendArgs[1]->value)->getConstantStrings()
                        );
                        if (count($cacheKey) === 0) {
                            continue;
                        }

                        if (isset($setCacheBackendArgs[2])) {
                            $cacheTagsType = $scope->getType($setCacheBackendArgs[2]->value);
                            foreach ($cacheTagsType->getConstantArrays() as $constantArray) {
                                foreach ($constantArray->getValueTypes() as $valueType) {
                                    foreach ($valueType->getConstantStrings() as $cacheTagConstantString) {
                                        foreach ($cacheKey as $cacheKeyValue) {
                                            if (strpos($cacheTagConstantString->getValue(), $cacheKeyValue) === false) {
                                                $misnamedCacheTagWarnings[] = $cacheTagConstantString->getValue();
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        break;
                    }
                }
                // Classes which have __construct() with a parent::__construct() to a class having setCacheBackend() in
                // their __construct() don't need further checking.

                // First make sure there is a  parent::__construct() in our __construct().
//                foreach ($statement->getStmts() as $constructStatement) {
//                    if ($constructStatement->expr instanceof Node\Expr\StaticCall
//                        && $constructStatement->expr->class->toString() === 'parent'
//                        && $constructStatement->expr->name->toString() === '__construct') {
//                        $a=1;
//                    }
//                }
            }
        }
        $errors = [];
        if (!$hasCacheBackendSet) {
            $errors[] = '__construct() Missing setCacheBackend() cache backend declaration for performance.';
        }
        foreach ($misnamedCacheTagWarnings as $cacheTagWarning) {
            $errors[] = sprintf('%s Cache tag in setCacheBackend() in the __construct() might be unclear and does not contain the cache key in it.', $cacheTagWarning);
        }

        return $errors;
    }
}
