# CONTRIBUTING

This bundle was originally created for M6Web projects purpose. As we strongly believe in open source, we share it to you.

If you want to learn more about our opinion on open source, you can read the [OSS article](http://tech.m6web.fr/oss/) on our website.

## Developing

The features available for now are only those we need, but you're welcome to open an issue or pull-request if you need more.

To ensure good code quality, we use [PHP-CS-Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer) to check there is no coding standards violations.

To execute PHP-CS-Fixer, you need to install dependencies in dev mode
```bash
composer install
```

And you can launch php-cs-fixer
```bash
./bin/php-cs-fixer --dry-run --using-cache=no --verbose --diff
```

## Testing

This bundle is tested with [atoum](https://github.com/atoum/atoum).

To launch tests, you need to install dependencies in dev mode
```bash
composer install
```

And you can now launch tests
```bash
bin/atoum
```

## Pull-request

If you are currently reading this section, you are a really good guy who share our vision about open source.

So, we don't want to harass you with tons of constraints. There is only 2 things we care about :
  * testing
  * coding standards
