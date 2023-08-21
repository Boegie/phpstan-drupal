<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Drupal\PluginManager\PluginManagerSetsCacheBackendRulez;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\Rule;

final class PluginManagerSetsCacheBackendRuleTest extends DrupalRuleTestCase
{

    protected function getRule(): Rule
    {
        return new PluginManagerSetsCacheBackendRulez(self::createReflectionProvider());
    }

    /**
     * @dataProvider ruleData
     */
    public function testRule(string $path, array $errorMessages): void
    {
        $this->analyse([$path], $errorMessages);
    }

    public static function ruleData(): \Generator
    {
        yield [
            __DIR__ . '/data/plugin-manager-cache-backend.php',
            [
                [
                    '__construct() Missing setCacheBackend() cache backend declaration for performance.',
                    9
                ],
                [
                    'plugins Cache tag in setCacheBackend() in the __construct() might be unclear and does not contain the cache key in it.',
                    109,
                ]
            ]
        ];

        yield [
            __DIR__ . '/data/test-cases-598.php',
            []
        ];
    }
}
