<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRedis\Cache;

use EzSystems\EzPlatformRedis\Cache\Serializer\IgbinarySerializer;
use EzSystems\EzPlatformRedis\Cache\Serializer\LZFCompressor;
use EzSystems\EzPlatformRedis\Cache\Serializer\NativeSerializer;

class ItemSerializerFactory
{
    /**
     * @var bool
     */
    private $igbinary;

    /**
     * @var bool
     */
    private $lzf;

    public function __construct(bool $igbinary, bool $lzf)
    {
        $this->igbinary = $igbinary;
        $this->lzf = $lzf;
    }

    /**
     * @return ItemSerializerInterface
     */
    public function create(): ItemSerializerInterface
    {
        $serializer = null;

        if ($this->igbinary) {
            $serializer = new IgbinarySerializer();
        } else {
            $serializer = new NativeSerializer();
        }

        if ($this->lzf) {
            $serializer = new LZFCompressor($serializer);
        }

        return $serializer;
    }
}
