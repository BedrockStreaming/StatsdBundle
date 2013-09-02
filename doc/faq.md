# FAQ

## no magic stuff for collecting my memory or other automagic things

nope. We believe that it's not the bundle reponsability to do that. However you can read at the [usage chapter](usage.md) how to do that.

## any todo ?

 * give access to the sampling rate in the bundle
 * give access to the decrement method via the event binding in the bundle


Don't hesitate to propose a Pull Request


## is there internal tests

powered by [Atoum](http://docs.atoum.org/)

```sh
$ php composer.phar install --dev
$ ./vendor/bin/atoum -d src/M6Web/Component/Tests -d src/M6Web/Bundle/StatsdBundle/Tests
```
