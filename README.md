# eZ Platform Redis
This package provides possibility to benefit from using `igbinary` serializer when using Redis. 

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
