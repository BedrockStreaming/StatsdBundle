# CONTRIBUTING

This bundle was originaly created for M6Web projects purpose. As we strongly believe in open source, we share it to you.

If you want to learn more about our opinion on open source, you can read the [OSS article](http://tech.m6web.fr/oss/) on our website.

## Developing

We implemented in this bundle the statsd sections we needed to and it is quite possible that some sections are missing. If you need some missing section, feel free to open an issue or a pull-request.

## Testing

This bundle is tested using [atoum](https://github.com/atoum/atoum).

To launch tests, you need to install dependancies in dev mode
```bash
composer install --dev
```

And you can now launch tests
```bash
./vendor/bin/atoum
```

No pull-request will be merged if tests execution fails.
