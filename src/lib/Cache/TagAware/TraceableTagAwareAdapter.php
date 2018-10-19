<?php

/**
 * File containing the ContentHandler implementation.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRedis\Cache\TagAware;

use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\Adapter\TraceableAdapter;

/**
 * @todo Config
 */
final class TraceableTagAwareAdapter extends TraceableAdapter implements TagAwareAdapterInterface
{
    /**
     * @param array $tags
     *
     * @return bool|void
     */
    public function invalidateTags(array $tags)
    {
        $event = $this->start(__FUNCTION__);
        try {
            return $event->result = $this->pool->invalidateTags();
        } finally {
            $event->end = microtime(true);
        }
    }
}
