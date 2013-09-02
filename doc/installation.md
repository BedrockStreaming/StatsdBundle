# Installation

Add this line to your `composer.json` :

```json
{
    "require": {
        "m6web/statsd-bundle": "@stable"
    }
}
```

Update your vendors :

```
composer update m6web/statsd-bundle
```

## Registering

```php
class AppKernel extends \Symfony\Component\HttpKernel\Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            new M6Web\Bundle\StatsdBundle\M6WebStatsdBundle(),
        );
    }
}
```

For the configuration read the [usage part](usage.md) of the documentation.

[TOC](toc.md)