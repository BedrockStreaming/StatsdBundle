# Usage


## Configure the servers

You have to configure the m6_statsd extension with the list of all the servers that will be available.

```json
m6_statsd:
    servers:
        default:
            address: 'udp://localhost'
            port:     1234
        serv1:
            address: 'udp://lolcaThost'
            port:     1235
        serv2:
            address: 'udp://lolcaThost'
            port:     1236
        clients:
            default:
                servers:   ["default"]        # the 'default' client will use only the default server
            swag:
                servers:   ["serv1", "serv2"] # the 'swag' client will use serv1 OR serv2 to send the datas
            mighty:
                servers: ['all'] # use all servers configured
```


You have to use the same graphite server behind statsd instance in order to see your stats.


## Basic usage

In a Symfony controller :

```php
// m6_statsd access to the default client by convention
$this->get('m6_statsd')->increment('a.graphite.node');
$this->get('m6_statsd')->timing('another.graphite.node', (float) $timing);
// access to the swag client
$this->get('m6_statsd.swag')->increment('huho');
```

By default, the send method of each clients is called on the kernel.terminate event. But you can call it manually in a controller :

```php
$this->get('m6_statsd')->send();
```

## Bind on events

### Increments

We don't really like mixing our business code with monitoring stuff. We prefer launch events with significant informations, listen to them, and send our monitoring stuffs in the listeners. The good news is that StastdBundle are doing it for you.

At each client level, you can specify events listened in order to build statsd increment or timing based on them.
For example, with the following configuration :

```yaml
clients:
    events:
        forum.read:
            increment : mysite.forum.read
```

On the Symfony event dispatcher, when the ```forum.read``` event is fired, our statds client catch this event and add to this queue the increment on the ```mysite.forum.read``` node.

So you can now just fire the event from a controller :
```php
$this->get('event_dispatcher')->dispatch('forum.read', new Symfony\Component\EventDispatcher\Event());
```

It's also possible to create tokens in the Symfony configuration, allowing you to pass custom value in the node.

The resolution of the token will be based on a method or a propertie of the event given.


```yaml
clients:
    events:
        forum.read:
            increment : mysite.forum.<name>.read
```

The event dispatched must have a getName method implemented or a $name public propertie.

### Timers

```yaml
clients:
    events:
        action.longaction:
            timing : timer.mysite.action
```

In this case, we will add a timer on timer.mysite.action node (of course you can still use this notation : `timer.<site>.action`). The timer value will be the output of the `getTiming` method of the event.

```yaml
clients:
    events:
        action.longaction:
            custom_timing : { node : timer.mysite.action, method : getRaoul }
```

The `custom_timing` allow you to set a custom method to collect the timer (here `getRaoul`).

**You can add multiple timing and increments under an event**

```yaml
clients:
    events:
        action.longaction:
            custom_timing : { node : timer.mysite.action, method : getRaoul }
            timing : timer.mysite.action
            timing : timer.mysite.action2
            increment : mysite.forum.<name>.read
            increment : mysite.forum.<name>.read2
            # ...
```

## Collect basics metrics on your Symfony application

Comparing to others bundle related to statsd, we choose not to implement the collect of those metrics natively in the bundle. But please find below some hints to do it on your own.

Basics metrics can be http code, memory consumption, execution time. Thoses metrics can be collected when the `kernel.terminate` event.

At m6web we extend the HttpKernel. In this class we can easily add a value to store, when the constructor is called, the current timestamp.

[example](https://gist.github.com/omansour/6412271#file-m6kernel-php)

You can custom an event to return the amount of memory consumed :

[example](https://gist.github.com/omansour/6412271#file-kernelterminateevent-php)

And so on ...

## DATA collector

TODO


## Using the component only

(if you working with Zend Framework or whatever !)

```php
use \M6Web\Component\Statsd;
$statsd = new Statsd\Client(
    array(
        'serv1' => array('address' => 'udp://xx.xx.xx.xx', 'port' => 'xx'),
        'serv2' => array('address' => 'udp://xx.xx.xx.xx', 'port' => 'xx'))
);
$statsd->increment('service.coucougraphite');
// we can also pass a sampling rate, default value is 1
$statsd->decrement('service.coucougraphite')->increment('service.test')->timing('service.timeismoney', 0.2);
// ..
$statsd->send();
```

[TOC](toc.md)
