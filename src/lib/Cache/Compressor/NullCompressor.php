<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRedis\Cache\Compressor;

use EzSystems\EzPlatformRedis\Cache\ItemCompressorInterface;

class NullCompressor implements ItemCompressorInterface
{
    public function compress($data): string
    {
        return $data;
    }

    public function decompress(string $compressedItem)
    {
        return $compressedItem;
    }
}
