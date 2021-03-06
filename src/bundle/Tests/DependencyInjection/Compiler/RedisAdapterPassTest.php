<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRedisBundle\Tests\DependencyInjection\Compiler;

use EzSystems\EzPlatformRedis\Cache\Adapter\RedisAdapter;
use EzSystems\EzPlatformRedis\Cache\ItemSerializerInterface;
use EzSystems\EzPlatformRedis\Cache\MarshallerInterface;
use EzSystems\EzPlatformRedisBundle\DependencyInjection\Compiler\RedisAdapterPass;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\Cache\Adapter\RedisAdapter as SymfonyRedisAdapter;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class RedisAdapterPassTest extends AbstractCompilerPassTestCase
{
    protected function setUp()
    {
        parent::setUp();
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RedisAdapterPass());
    }

    public function testProcess(): void
    {
        $redisMock = $this->getMockBuilder(\Redis::class)
            ->disableOriginalConstructor()
            ->getMock();

        $cacheAdapterRedisDefinition = new Definition(SymfonyRedisAdapter::class, [$redisMock, '']);
        $cacheAdapterRedisDefinition->setPublic(true);
        $this->setDefinition('cache.adapter.redis', $cacheAdapterRedisDefinition);

        $this->setParameter('ezplatform_redis.serializer', 'igbinary');
        $this->setParameter('ezplatform_redis.compressor', 'lzf');

        $this->compile();

        $this->assertContainerBuilderHasParameter('ezplatform_redis.serializer');
        $this->assertContainerBuilderHasParameter('ezplatform_redis.compressor');
        $this->assertContainerBuilderHasService('cache.adapter.redis', RedisAdapter::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('cache.adapter.redis', 2, new Reference(MarshallerInterface::class));
    }
}
