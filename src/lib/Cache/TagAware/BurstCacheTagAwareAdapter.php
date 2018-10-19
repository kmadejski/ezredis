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
use Psr\Cache\CacheItemInterface;

/**
 * @todo Rewrite to adapt for this repo, and extend ArrayAdapter? (probably not, it does not decorate but act as own for use with chaining)
 */
final class BurstCacheTagAwareAdapter implements TagAwareAdapterInterface
{
    private const DEFAULT_LIMIT = 100;
    private const DEFAULT_TTL = 3;

    /**
     * @var int
     */
    private $limit;

    /**
     * @var int
     */
    private $ttl;

    public function __construct(TagAwareAdapterInterface $pool, int $limit = self::DEFAULT_LIMIT, int $ttl = self::DEFAULT_TTL)
    {
        $this->pool = $pool;
        $this->limit = $limit;
        $this->ttl = $ttl;
    }

    public function getItem($key)
    {
        if ($items = $this->getValidInMemoryCacheItems([$key])) {
            return $items[$key];
        }

        $item = $this->pool->getItem($key);
        if ($item->isHit()) {
            $this->saveCacheHitsInMemory([$key => $item]);
        }

        return $item;
    }

    public function getItems(array $keys = [])
    {
        $missingKeys = [];
        foreach ($this->getValidInMemoryCacheItems($keys, $missingKeys) as $key => $item) {
            yield $key => $item;
        }

        if (!empty($missingKeys)) {
            $hits = [];
            $items = $this->pool->getItems($missingKeys);
            foreach ($items as $key => $item) {
                yield $key => $item;

                if ($item->isHit()) {
                    $hits[$key] = $item;
                }
            }

            $this->saveCacheHitsInMemory($hits);
        }
    }

    public function hasItem($key)
    {
        // We are not interested in trying to cache if we don't have the item, but if we do we can return true
        if (isset($this->cacheItems[$key])) {
            return true;
        }

        return $this->pool->hasItem($key);
    }

    public function clear()
    {
        $this->cacheItems = [];
        $this->cacheItemsTS = [];

        return $this->pool->clear();
    }

    public function deleteItem($key)
    {
        if (isset($this->cacheItems[$key])) {
            unset($this->cacheItems[$key], $this->cacheItemsTS[$key]);
        }

        return $this->pool->deleteItem($key);
    }

    public function deleteItems(array $keys)
    {
        foreach ($keys as $key) {
            if (isset($this->cacheItems[$key])) {
                unset($this->cacheItems[$key], $this->cacheItemsTS[$key]);
            }
        }

        return $this->pool->deleteItems($keys);
    }

    public function save(CacheItemInterface $item)
    {
        $this->saveCacheHitsInMemory([$item->getKey() => $item]);

        return $this->pool->save($item);
    }

    public function saveDeferred(CacheItemInterface $item)
    {
        // Symfony commits the deferred items as soon as getItem(s) is called on it later or on destruct.
        // So seems we can safely save in-memory, also we don't at the time of writing use saveDeferred().
        $this->saveCacheHitsInMemory([$item->getKey() => $item]);

        return $this->pool->saveDeferred($item);
    }

    public function commit()
    {
        return $this->pool->commit();
    }

    public function invalidateTags(array $tags)
    {
        // Cleanup in-Memory cache items affected
        foreach ($this->cacheItems as $key => $item) {
            if (array_intersect($item->getPreviousTags(), $tags)) {
                unset($this->cacheItems[$key], $this->cacheItemsTS[$key]);
            }
        }

        return $this->pool->invalidateTags($tags);
    }

    /**
     * @param \Psr\Cache\CacheItemInterface[] $items Save Cache hits in-memory with cache key as array key.
     */
    private function saveCacheHitsInMemory(array $items): void
    {
        // Skip if empty
        if (empty($items)) {
            return;
        }

        // If items accounts for more then 20% of our limit, assume it's bulk content load and skip saving in-memory
        if (\count($items) >= $this->limit / 5) {
            return;
        }

        // Will we stay clear of the limit? If so return early
        if (\count($items) + \count($this->cacheItems) < $this->limit) {
            $this->cacheItems += $items;
            $this->cacheItemsTS += \array_fill_keys(\array_keys($items), \time());

            return;
        }

        // # Vacuuming cache in bulk so we don't end up doing this all the time
        // 1. Discriminate against content cache, remove up to 1/3 of max limit starting from oldest items
        $removeCount = 0;
        $removeTarget = \floor($this->limit / 3);
        foreach ($this->cacheItems as $key => $item) {
            $cache = $item->get();
            foreach (self::CONTENT_CLASSES as $className) {
                if ($cache instanceof $className) {
                    unset($this->cacheItems[$key]);
                    ++$removeCount;

                    break;
                }
            }

            if ($removeCount >= $removeTarget) {
                break;
            }
        }

        // 2. Does cache still exceed the 66% of limit? if so remove everything above 66%
        // NOTE: This on purpose keeps the oldest cache around, getValidInMemoryCacheItems() handles ttl checks on that
        if (\count($this->cacheItems) >= $this->limit / 1.5) {
            $this->cacheItems = \array_slice($this->cacheItems, 0, \floor($this->limit / 1.5));
        }

        $this->cacheItems += $items;
        $this->cacheItemsTS += \array_fill_keys(\array_keys($items), \time());
    }

    /**
     * @param array $keys
     * @param array $missingKeys
     *
     * @return array
     */
    public function getValidInMemoryCacheItems(array $keys = [], array &$missingKeys = []): array
    {
        // 1. Validate TTL and remove items that have exceeded it (on purpose not prefixed for global scope, see tests)
        $expiredTime = time() - $this->ttl;
        foreach ($this->cacheItemsTS as $key => $ts) {
            if ($ts <= $expiredTime) {
                unset($this->cacheItemsTS[$key]);

                // Cache items might have been removed in saveInMemoryCacheItems() when enforcing limit
                if (isset($this->cacheItems[$key])) {
                    unset($this->cacheItems[$key]);
                }
            }
        }

        // 2. Get valid items
        $items = [];
        foreach ($keys as $key) {
            if (isset($this->cacheItems[$key])) {
                $items[$key] = $this->cacheItems[$key];
            } else {
                $missingKeys[] = $key;
            }
        }

        return $items;
    }
}
