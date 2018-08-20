<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRedis\Cache;

use EzSystems\EzPlatformRedis\Cache\Serializer\IgbinarySerializer;
use EzSystems\EzPlatformRedis\Cache\Serializer\NativeSerializer;

class ItemSerializerFactory
{
    /**
     * @return ItemSerializerInterface
     */
    public function create(): ItemSerializerInterface
    {
        $nativeSerializer = new NativeSerializer();

        if (\extension_loaded('igbinary')) {
            return new IgbinarySerializer($nativeSerializer);
        }
    }
}
