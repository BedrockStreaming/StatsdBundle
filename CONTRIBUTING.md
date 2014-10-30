# CONTRIBUTING

This bundle was originaly created for M6Web projects purpose. As we strongly believe in open source, we share it to you.

If you want to learn more about our opinion on open source, you can read the [OSS article](http://tech.m6web.fr/oss/) on our website.

## Developing

We implemented in this bundle the statsd sections we needed to and it is quite possible that some sections are missing. If you need some missing section, feel free to open an issue or a pull-request.

To ensure good code quality, we use our awesome tool "[coke](https://github.com/M6Web/Coke)" to check there is no coding standards violations. 
We use [Symfony2 coding standards](https://github.com/M6Web/Symfony2-coding-standard).

To execute coke, you need to install dependencies in dev mode
```bash
composer install --dev
```

And you wan launch coke
```bash
./vendor/bin/coke
```

## Testing

This bundle is tested using [atoum](https://github.com/atoum/atoum).

To launch tests, you need to install dependencies in dev mode
```bash
composer install --dev
```

And you can now launch tests
```bash
./vendor/bin/atoum
```

## Pull-request

If you are currently reading this section, you are a really a good guys who share our vision about open source.

So, we don't want to harass you with tons of constraints. There is only 2 things we care about :
  * testing
  * coding standards
