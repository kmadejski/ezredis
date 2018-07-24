<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRedis\Cache\Marshaller;

use EzSystems\EzPlatformRedis\Cache\ItemCompressorInterface;
use EzSystems\EzPlatformRedis\Cache\ItemSerializerInterface;
use EzSystems\EzPlatformRedis\Cache\MarshallerInterface;

class DefaultMarshaller implements MarshallerInterface
{
    /**
     * @var ItemSerializerInterface
     */
    private $serializer;

    /**
     * @var ItemCompressorInterface
     */
    private $compressor;

    public function __construct(ItemSerializerInterface $serializer, ItemCompressorInterface $compressor)
    {
        $this->serializer = $serializer;
        $this->compressor = $compressor;
    }

    public function marshall($item): string
    {
        return $this->compressor->compress($this->serializer->serialize($item));
    }

    public function unmarshall(string $item)
    {
        return $this->serializer->unserialize($this->compressor->decompress($item));
    }
}
