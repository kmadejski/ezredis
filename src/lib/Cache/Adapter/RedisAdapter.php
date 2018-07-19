<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRedis\Cache\Adapter;

use EzSystems\EzPlatformRedis\Cache\ItemSerializerInterface;
use EzSystems\EzPlatformRedis\Cache\Serializer\IgbinarySerializer;
use Predis\Response\Status;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Traits\RedisTrait;

class RedisAdapter extends AbstractAdapter
{
    use RedisTrait;

    /**
     * @var ItemSerializerInterface
     */
    private static $serializer;

    public function __construct($redisClient, $namespace = '', $defaultLifetime = 0, ItemSerializerInterface $serializer = null)
    {
        self::$serializer = $serializer ?? new IgbinarySerializer();

        $this->init($redisClient, $namespace, $defaultLifetime);
    }

    /**
     * This method overrides @see \Symfony\Component\Cache\Traits\AbstractTrait::unserialize
     * It needs to be overridden due to the usage of native `unserialize` method in the original method.
     *
     * @param $value
     *
     * @return mixed|void
     *
     * @throws \ErrorException
     */
    protected static function unserialize($value)
    {
        if ('b:0;' === $value) {
            return false;
        }
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
     * @param array $values
     * @param int $lifetime
     *
     * @return array|bool|void
     */
    protected function doSave(array $values, $lifetime)
    {
        $serialized = [];
        $failed = [];

        foreach ($values as $id => $value) {
            try {
                $serialized[$id] = self::$serializer->serialize($value);
            } catch (\Exception $e) {
                $failed[] = $id;
            }
        }

        if (!$serialized) {
            return $failed;
        }

        $results = $this->pipeline(function () use ($serialized, $lifetime) {
            foreach ($serialized as $id => $value) {
                if (0 >= $lifetime) {
                    yield 'set' => [$id, $value];
                } else {
                    yield 'setEx' => [$id, $lifetime, $value];
                }
            }
        });
        foreach ($results as $id => $result) {
            if (true !== $result && (!$result instanceof Status || $result !== Status::get('OK'))) {
                $failed[] = $id;
            }
        }

        return $failed;
    }

    /**
     * This method overrides @see \Symfony\Component\Cache\Traits\RedisTrait::doFetch
     * It needs to be overridden due to the usage of `parent::unserialize()` in the original method, which points to.
     *
     * @see \Symfony\Component\Cache\Traits\AbstractTrait::unserialize instead of the one defined in this class.
     *
     * @param array $ids The cache identifiers to fetch
     *
     * @return array|\Traversable The corresponding values found in the cache
     *
     * @throws \ErrorException
     */
    protected function doFetch(array $ids)
    {
        if ($ids) {
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
    }
}
