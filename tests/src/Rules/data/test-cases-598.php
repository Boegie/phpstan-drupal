<?php

namespace TestCases598;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\node\Entity\Node;

class IgnoreClassesNotExtendingAnyClass
{

    public function __construct()
    { }

}
class IgnoreClassesNotImplementingDrupalComponentPluginPluginManagerInterface extends Node
{

    public function __construct()
    { }

}

class IgnoreClassesWhichImplementSetCacheBackend extends PluginManagerBase
{

    public function __construct(CacheBackendInterface $cache_backend)
    { }

    public function setCacheBackend(CacheBackendInterface $cache_backend, $cache_key, array $cache_tags = [])
    { }

}

class IgnoreClassesWhichHaveParentClassHavingSetCacheBackendInConstruct extends ClassHavingSetCacheBackendInConstruct
{

    public function __construct(CacheBackendInterface $cache_backend)
    {
        parent::__construct($cache_backend);
    }

}


class ClassHavingSetCacheBackendInConstruct extends DefaultPluginManager {

    public function __construct(CacheBackendInterface $cache_backend)
    {
        $this->setCacheBackend($cache_backend, 'cache.key', []);
    }

}

