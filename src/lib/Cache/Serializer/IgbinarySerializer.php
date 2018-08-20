<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRedis\Cache\Serializer;

use EzSystems\EzPlatformRedis\Cache\ItemSerializerInterface;

class IgbinarySerializer implements ItemSerializerInterface
{
    /**
     * @var ItemSerializerInterface
     */
    private $nativeSerializer;

    public function __construct(ItemSerializerInterface $serializer)
    {
        $this->nativeSerializer = $serializer;
    }

    public function serialize($item): string
    {
        return igbinary_serialize($item);
    }

    public function unserialize(string $serializedItem)
    {
        // serialized `false`
        if ('b:0;' === $serializedItem) {
            return false;
        }

        // serialized `null`
        if ('N;' === $serializedItem) {
            return null;
        }

        // when the character with index 1 is `:` then we can assume
        // that the content has been serialized using native serializer
        if (':' === ($serializedItem[1] ?? ':')) {
            if (false !== $value = $this->nativeSerializer->unserialize($serializedItem)) {
                return $value;
            }
        }

        return igbinary_unserialize($serializedItem);
    }
}
