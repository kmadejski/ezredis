<?php

namespace EzSystems\EzPlatformRedis\Cache;

use EzSystems\EzPlatformRedis\Cache\Marshaller\DefaultMarshaller;

class MarshallerFactory
{
    /**
     * @var ItemSerializerInterface
     */
    private $serializer;
    /**
     * @var ItemCompressorInterface
     */
    private $compressor;

    public function __construct(ItemSerializerInterface $serializer, ItemCompressorInterface $compressor, string $marshaller = '')
    {
        $this->serializer = $serializer;
        $this->compressor = $compressor;
    }

    public function create(): MarshallerInterface
    {
        return new DefaultMarshaller($this->serializer, $this->compressor);
    }
}