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
```

**Attention !** If you dispatch your command on several server, you have to re-assemble in order to aggregate it to graphite.

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

We don't really like coupling our business code to monitoring stuff. We prefer launch events with significant informations, listen to them, and send our monitoring stuffs in the listeners. The good news is that StastdBundle are doing it for you.

Au niveau de chaque client, on peut configurer les évènements que celui-ci écoute afin de les transformer en increment statsd.
Par exemple, en spécifiant la configuration suivante:

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
The resolution of the token will be based on the method or propertie of the event given.





## Collect basics metrics on your Symfony application

TODO


## Using the component only

(if you working with Zend Framework or whatever !)

```php
use \M6Web\Component\Statsd;
$statsd = new Statsd\Client(
    array(
        'serv1' => array('adress' => 'udp://xx.xx.xx.xx', 'port' => 'xx'),
        'serv2' => array('adress' => 'udp://xx.xx.xx.xx', 'port' => 'xx'))
);
$statsd->increment('service.coucougraphite');
// we can also pass a sampling rate, default value is 1
$statsd->decrement('service.coucougraphite')->increment('service.test')->timing('service.timeismoney', 0.2);
// ..
$statsd->send();
```