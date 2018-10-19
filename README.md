# Symfony Cache Extra Bundle

This bundle provides optimized variants of TagAware Symfony Cache implementations
and aims to serve as incubator for ideas to improve Symfony Cache in the future. If
these features are accepted in Symfony this package will for those features aim to
serve as polyfill for earlier Symfony versions (3+).

This bundle is first and foremost aiming to cover needs of [eZ Platform](https://ezplatform.com),
but is placed in own bundle under MIT as we think others can benefit and help collaborate
towards the goals here.

## Planned features

Aim is to provide the following TagAware Symfony Cache implementations:
- RedisTagAwareAdapter
- FilesystemTagAwareAdapter _(Or PHPFiles if we find ways to get that to work reliably, but afaik it's not recommended)_
- TraceableTagAwareAdapter _(needed in order to allow logging of cache usage with these adapters)_
- BurstCacheTagAwareAdapter _(TBD)_


### Design

Redis and Filesystem adapters aim to solve performance issues with TagAware implementation in Symfony.
This is done by modeling Tags as relations instead of sperated keys with invalidation by timestamp which
then needs to be loaded all the time.

This is accomplished using:
- Redis: Using [Sets](https://redis.io/topics/data-types#sets)
    - _Redis 3.2 & PHP-Redis 3.1.3+/Predis v1 is required as this is using [SPOP](https://redis.io/commands/spop) with count argument_
    - Supported [eviction policies](https://redis.io/topics/lru-cache) are `noeviction`, or any `volatile-*` policy.
      _This is to make sure tags are never evicted before cache items, as tags are stored without expiry &
      cache items gets a default ttl if not set to make this possible._
- FileSystem: Using directory representing Tag and symlinks representing relationship to key items.


On top of this the Adapters support usage of `igbinary` if present to:
- reduce cache size
- reduce transfer size
- faster un-serialization


FAQ:
- Memcached?
  Can be considered, but would require to have a reliable approach to make sure tags are never evicted by [LRU](https://github.com/memcached/memcached/wiki/UserInternals#when-are-items-evicted)
  before cached items, even it might become considered "COLD" as it's not read from unless invalidation occurs.


TODO:
- impl
- LICENSE: MIT
- Rename namespace
- add tests
- Run Symfony Cache test suite if applicable? _if so setup travis across supported Symfony & PHP versions._

# Installation
Run the following from your eZ Platform installation root:
```bash
composer require ezsystems/ezplatform-redis:dev-master
```
Enable the bundle in app/AppKernel.php:
```php
$bundles = array(
    // existing bundles
    new EzSystems\EzPlatformRedisBundle\EzPlatformRedisBundle(),
);
```

# Configuration
The bundle does not need any additional configuration.
