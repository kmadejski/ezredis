<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRedis\Tests\Cache;

use EzSystems\EzPlatformRedis\Cache\Compressor\LZFCompressor;
use EzSystems\EzPlatformRedis\Cache\Compressor\NullCompressor;
use EzSystems\EzPlatformRedis\Cache\ItemCompressorFactory;
use EzSystems\EzPlatformRedis\Cache\ItemSerializerFactory;
use EzSystems\EzPlatformRedis\Cache\Marshaller\DefaultMarshaller;
use EzSystems\EzPlatformRedis\Cache\MarshallerFactory;
use EzSystems\EzPlatformRedis\Cache\Serializer\IgbinarySerializer;
use EzSystems\EzPlatformRedis\Cache\Serializer\NativeSerializer;
use PHPUnit\Framework\TestCase;

class MarshallerFactoryTest extends TestCase
{
    /**
     * @dataProvider getConfigurations
     *
     * @param string $serializer
     * @param string $compressor
     * @param string $marshaller
     * @param string $expectedMarshallerClass
     * @param string $expectedSerializerClass
     * @param string $expectedCompressorClass
     */
    public function testBuild(
        string $serializer,
        string $compressor,
        string $marshaller,
        string $expectedSerializerClass,
        string $expectedCompressorClass,
        string $expectedMarshallerClass
    ): void {

        $itemSerializerFactory = new ItemSerializerFactory($serializer);
        $serializer = $itemSerializerFactory->create();
        $this->assertInstanceOf($expectedSerializerClass, $serializer);

        $itemCompressorFactory = new ItemCompressorFactory($compressor);
        $compressor = $itemCompressorFactory->create();
        $this->assertInstanceOf($expectedCompressorClass, $compressor);

        $marshallerFactory = new MarshallerFactory($serializer, $compressor, $marshaller);
        $marshaller = $marshallerFactory->create();
        $this->assertInstanceOf($expectedMarshallerClass, $marshaller);
    }

    public function getConfigurations(): array
    {
        return [
            [
                'igbinary',
                'none',
                'default',
                IgbinarySerializer::class,
                NullCompressor::class,
                DefaultMarshaller::class,
            ],
            [
                'igbinary',
                'lzf',
                'default',
                IgbinarySerializer::class,
                LZFCompressor::class,
                DefaultMarshaller::class
            ],
            [
                'native',
                'lzf',
                'default',
                NativeSerializer::class,
                LZFCompressor::class,
                DefaultMarshaller::class,
            ],
        ];
    }
}
