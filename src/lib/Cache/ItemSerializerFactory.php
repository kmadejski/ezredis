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
     * @var string
     */
    private $serializer;

    public function __construct(string $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @return ItemSerializerInterface
     */
    public function create(): ItemSerializerInterface
    {
        if ($this->serializer === 'igbinary') {
            return new IgbinarySerializer();
        }

        return new NativeSerializer();
    }
}
