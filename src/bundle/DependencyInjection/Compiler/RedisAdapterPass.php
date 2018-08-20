<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRedisBundle\DependencyInjection\Compiler;

use EzSystems\EzPlatformRedis\Cache\Adapter\RedisAdapter;
use EzSystems\EzPlatformRedis\Cache\ItemSerializerInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RedisAdapterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('cache.adapter.redis')) {
            return;
        }

        $redisAdapterDef = $container->findDefinition('cache.adapter.redis');
        $redisAdapterDef->setClass(RedisAdapter::class);
        $redisAdapterDef->addArgument(new Reference(ItemSerializerInterface::class));
    }
}
