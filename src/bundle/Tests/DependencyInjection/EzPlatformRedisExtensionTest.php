<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRedisBundle\Tests\DependencyInjection;

use EzSystems\EzPlatformRedisBundle\DependencyInjection\EzPlatformRedisExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;

class EzPlatformRedisExtensionTest extends AbstractExtensionTestCase
{
    /**
     * @var \EzSystems\EzPlatformRedisBundle\DependencyInjection\EzPlatformRedisExtension
     */
    private $extension;

    protected function setUp()
    {
        $this->extension = new EzPlatformRedisExtension();
        parent::setUp();
    }

    protected function getContainerExtensions(): array
    {
        return [$this->extension];
    }

    public function testExtension(): void
    {
        $configuration = [
            'igbinary' => false,
            'lzf' => false,
        ];

        $this->load($configuration);

        $this->assertTrue($this->container->hasParameter('ezplatform_redis.igbinary'));
        $this->assertTrue($this->container->hasParameter('ezplatform_redis.lzf'));
        $this->assertSame($configuration['igbinary'], $this->container->getParameter('ezplatform_redis.igbinary'));
        $this->assertSame($configuration['lzf'], $this->container->getParameter('ezplatform_redis.lzf'));
    }
}
