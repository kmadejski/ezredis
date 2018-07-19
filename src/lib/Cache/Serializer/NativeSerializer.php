<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRedis\Cache\Serializer;

use EzSystems\EzPlatformRedis\Cache\ItemSerializerInterface;

class NativeSerializer implements ItemSerializerInterface
{
    public function serialize($item): string
    {
        return serialize($item);
    }

    public function unserialize(string $serializedItem)
    {
        return unserialize($serializedItem);
    }
}
