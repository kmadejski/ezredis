<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRedis\Cache\Serializer;

use EzSystems\EzPlatformRedis\Cache\ItemSerializerInterface;

class LZFCompressor implements ItemSerializerInterface
{
    /**
     * @var ItemSerializerInterface
     */
    private $innerSerializer;

    public function __construct(ItemSerializerInterface $innerSerializer)
    {
        $this->innerSerializer = $innerSerializer;
    }

    public function serialize($item): string
    {
        return lzf_compress($this->innerSerializer->serialize($item));
    }

    public function unserialize(string $serializedItem)
    {
        return $this->innerSerializer->unserialize(lzf_decompress($serializedItem));
    }
}
