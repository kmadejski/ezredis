# eZ Platform Redis
This package provides possibility to benefit from using different data serializers and compressors when using Redis. 

Currently supported serializers:
- igbinary

Currently supported compressors:
- lzf

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
You have to configure the bundle in your `app/config/ezplatform.yml` or `app/config/config.yml` as above:

```yml
ez_platform_redis:
    igbinary: true
    lzf: true
```
 