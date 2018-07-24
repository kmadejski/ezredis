<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRedis\Cache;

use EzSystems\EzPlatformRedis\Cache\Compressor\LZFCompressor;
use EzSystems\EzPlatformRedis\Cache\Compressor\NullCompressor;

class ItemCompressorFactory
{
    /**
     * @var string
     */
    private $compressor;

    public function __construct(string $compressor)
    {
        $this->compressor = $compressor;
    }

    public function create(): ItemCompressorInterface
    {
        if ($this->compressor === 'lzf') {
            return new LZFCompressor();
        }

        return new NullCompressor();
    }
}
