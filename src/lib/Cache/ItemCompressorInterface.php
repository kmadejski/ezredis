<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRedis\Cache;

interface ItemCompressorInterface
{
    public function compress($data): string;

    public function decompress(string $compressedItem);
}
