<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRedisBundle\DependencyInjection\Compiler;

use EzSystems\EzPlatformRedis\Cache\Adapter\RedisAdapter;
use EzSystems\EzPlatformRedis\Cache\MarshallerInterface;
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

        $serializer = $container->hasParameter('ezplatform_redis.serializer') ? $container->getParameter('ezplatform_redis.serializer') : 'native';
        $compressor = $container->hasParameter('ezplatform_redis.compressor') ? $container->getParameter('ezplatform_redis.compressor') : 'none';

        // if none of the options is in use, then do nothing and fallback to the default Symfony's Redis adapter
        if (!$serializer && !$compressor) {
            return;
        }

        $redisAdapterDef = $container->findDefinition('cache.adapter.redis');
        $redisAdapterDef->setClass(RedisAdapter::class);
        $redisAdapterDef->addArgument(new Reference(MarshallerInterface::class));
    }
}
