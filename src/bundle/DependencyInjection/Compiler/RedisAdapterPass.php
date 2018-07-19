<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRedisBundle\DependencyInjection\Compiler;

use EzSystems\EzPlatformRedis\Cache\Adapter\RedisAdapter;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use EzSystems\EzPlatformRedis\Cache\ItemSerializerInterface;

class RedisAdapterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('cache.adapter.redis')) {
            return;
        }

        $igbinary = $container->hasParameter('ezplatform_redis.igbinary') ? $container->getParameter('ezplatform_redis.igbinary') : false;
        $lzf = $container->hasParameter('ezplatform_redis.lzf') ? $container->getParameter('ezplatform_redis.lzf') : false;

        // if none of the options is in use, then do nothing and fallback to the default Symfony's Redis adapter
        if (!$igbinary && !$lzf) {
            return;
        }

        $redisAdapterDef = $container->findDefinition('cache.adapter.redis');
        $redisAdapterDef->setClass(RedisAdapter::class);
        $redisAdapterDef->addArgument(new Reference(ItemSerializerInterface::class));
    }
}
