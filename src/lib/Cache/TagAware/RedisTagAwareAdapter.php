<?php

/**
 * File containing the ContentHandler implementation.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRedis\Cache\TagAware;

use EzSystems\EzPlatformRedis\Cache\ItemSerializerInterface;
use Predis;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\Traits\RedisTrait;

/**
 * Class RedisTagAwareAdapter
 *
 * Design compared to Symfony's TagAwareAdapter:
 * - Cache items are stored with:
 *   - Lifetime forced to 10days (in Redis, cache items are not touched) when no lifetime is set
 *   - Own serialization is used so we can use for instance Igbinary for smaller size & faster unserialization
 * - For tags instead of time based invalidation which needs to retrieve the timestamps all the time, use invalidation:
 *   - Use Redis Sets for Tags, appending related keys on the tags, with no expiry on the Set
 *   - Fetches and resets Set on invalidation by tag, in a pipeline operation.
 *
 * NOTE:
 * This is made so that with Redis configured with `noeviction` or any `volatile-*` eviction policy,
 * we grantee that tags ("relations") survives cache items so we can reliably invalidate on it.
 */
final class RedisTagAwareAdapter extends AbstractAdapter implements TagAwareAdapterInterface
{
    /**
     * Prefix used for tag Sets.
     */
    private const TAG_SET_PREFIX = "\0tag-set\0";

    /**
     * On cache items without a lifetime set, we force it to 10 days. See Class description for why.
     * @todo Should this instead be set as default lifetime in ctor?
     */
    private const FORCED_ITEM_TTL = 864000;

    /**
     * Max set size is set to max int size for PHP on 32bit, slightly above 2billion items it should be high enough and
     * is below Redis's 4billion limit on Set size. This is used in invalidateTags().
     */
    private const MAX_SET_SIZE = 2147483647;

    use RedisTrait;

    private $getTagSets;

    /**
     * Forced to be static due to ::unserialize().
     *
     * @var \EzSystems\EzPlatformRedis\Cache\ItemSerializerInterface
     */
    private static $serializer;

    public function __construct($redisClient, ItemSerializerInterface $serializer, $namespace = '', $defaultLifetime = 0)
    {
        self::$serializer = $serializer;

        $this->getTagSets = \Closure::bind(
            function ($items) {
                $tagSets = [];
                foreach ($items as $id => $item) {
                    foreach ($item->tags as $tag) {
                        // @todo Should find a way to use private AbstractAdapter->getId() to prefix with namespace.
                        $tagSets[self::TAG_SET_PREFIX.$tag][] = $id;
                    }
                }

                return $tagSets;
            },
            null,
            CacheItem::class
        );

        $this->init($redisClient, $namespace, $defaultLifetime);
    }

    /**
     * This method overrides @see \Symfony\Component\Cache\Traits\AbstractTrait::unserialize
     * It needs to be overridden due to the usage of native `unserialize` method in the original method.
     *
     * {@inheritdoc}
     */
    protected static function unserialize($value)
    {
        $unserializeCallbackHandler = ini_set('unserialize_callback_func', __CLASS__ . '::handleUnserializeCallback');
        try {
            if (false !== $value = self::$serializer->unserialize($value)) {
                return $value;
            }
            throw new \DomainException('Failed to unserialize cached value');
        } catch (\Error $e) {
            throw new \ErrorException($e->getMessage(), $e->getCode(), E_ERROR, $e->getFile(), $e->getLine());
        } finally {
            ini_set('unserialize_callback_func', $unserializeCallbackHandler);
        }
    }

    /**
     * This method overrides @see \Symfony\Component\Cache\Traits\RedisTrait::doSave
     * It needs to be overridden due to the usage of native `serialize` method in the original method.
     *
     * {@inheritdoc}
     */
    protected function doSave(array $items, $lifetime)
    {
        $serialized = [];
        $failed = [];

        /** @var \Symfony\Component\Cache\CacheItem $item */
        foreach ($items as $id => $item) {
            try {
                $serialized[$id] = self::$serializer->serialize($item);
            } catch (\Exception $e) {
                $failed[] = $id;
            }
        }

        if (empty($serialized)) {
            return $failed;
        }

        $f = $this->getTagSets;
        $tagSets = $f($items);

        $results = $this->pipeline(function () use ($serialized, $lifetime, $tagSets) {
            // 1: Store cache items
            foreach ($serialized as $id => $value) {
                // Note: There is no MSETEX so we need to set each one
                yield 'setEx' => [
                    $id,
                    0 >= $lifetime ? self::FORCED_ITEM_TTL : $lifetime,
                    $value
                ];
            }

            // 2: append tag sets
            // Method: Predis has different signature then Redis ext
            $sAddMethod = $this->redis instanceof Predis\Client ? 'sAdd' : 'sAddArray';
            foreach ($tagSets as $tagId => $ids) {
                yield $sAddMethod => [$tagId, $ids];
            }


        });

        // @todo We might need to ignore tag results here
        foreach ($results as $id => $result) {
            if (true !== $result && (!$result instanceof Status || $result !== Status::get('OK'))) {
                $failed[] = $id;
            }
        }

        return $failed;
    }

    /**
     * This method overrides @see \Symfony\Component\Cache\Traits\RedisTrait::doFetch
     * It needs to be overridden due to the usage of `parent::unserialize()` in the original method.
     *
     * {@inheritdoc}
     */
    protected function doFetch(array $ids)
    {
        if (empty($ids)) {
            return;
        }

        $values = $this->pipeline(function () use ($ids) {
            foreach ($ids as $id) {
                yield 'get' => [$id];
            }
        });
        foreach ($values as $id => $v) {
            if ($v) {
                yield $id => self::unserialize($v);
            }
        }
    }

    /**
     * @param array $tags
     *
     * @return bool|void
     */
    public function invalidateTags(array $tags)
    {
        if (empty($tags)) {
            return;
        }

        // Requires Predis or PHP Redis 3.1.3+ (https://github.com/phpredis/phpredis/commit/d2e203a6)
        $tagIds = $this->pipeline(function () use ($tags) {
            foreach ($tags as $tag) {
                yield 'sPop' => [self::TAG_SET_PREFIX.$tag, self::MAX_SET_SIZE];
            }
        });

        // flatten array, keys should already be prefixed as id's here
        $allIds = [];
        foreach ($tagIds as $ids) {
            $allIds += $ids;// array union as we don't care about key
        }

        // NOTE: If we didn't support Redis Cluster we could have done all in one pipeline, but besides negligible risk
        // of clearing in between generated cache items, only real downside here is extra rountrip.
        $this->doDelete(array_unique($allIds));

        return true;
    }
}
