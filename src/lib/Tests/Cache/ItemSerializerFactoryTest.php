<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRedis\Tests\Cache;

use EzSystems\EzPlatformRedis\Cache\ItemSerializerFactory;
use EzSystems\EzPlatformRedis\Cache\Serializer\IgbinarySerializer;
use EzSystems\EzPlatformRedis\Cache\Serializer\LZFCompressor;
use EzSystems\EzPlatformRedis\Cache\Serializer\NativeSerializer;
use PHPUnit\Framework\TestCase;

class ItemSerializerFactoryTest extends TestCase
{
    /**
     * @dataProvider getConfigurations
     *
     * @param bool $igbinary
     * @param bool $lzf
     * @param string $expectedSerializerClass
     * @param string|null $expectedDecoratedSerializerClass
     *
     * @throws \ReflectionException
     */
    public function testBuild(bool $igbinary, bool $lzf, string $expectedSerializerClass, string $expectedDecoratedSerializerClass = null): void
    {
        $itemSerializerFactory = new ItemSerializerFactory($igbinary, $lzf);
        $serializer = $itemSerializerFactory->create();

        $this->assertInstanceOf($expectedSerializerClass, $serializer);

        if (null !== $expectedDecoratedSerializerClass) {
            $reflection = new \ReflectionClass($serializer);
            $decoratedSerializer = $reflection->getProperty('innerSerializer');
            $decoratedSerializer->setAccessible(true);
            $decoratedSerializer = $decoratedSerializer->getValue($serializer);

            $this->assertInstanceOf($expectedDecoratedSerializerClass, $decoratedSerializer);
        }
    }

    public function getConfigurations(): array
    {
        return [
          [
              true,
              false,
              IgbinarySerializer::class,
              null,
          ],
          [
              true,
              true,
              LZFCompressor::class,
              IgbinarySerializer::class,
          ],
          [
              false,
              true,
              LZFCompressor::class,
              NativeSerializer::class,
          ],
        ];
    }
}
