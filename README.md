# StatsdBundle [![Build Status](https://travis-ci.org/M6Web/StatsdBundle.png?branch=master)](https://travis-ci.org/M6Web/StatsdBundle)

Bundle easing the [stasd](https://github.com/etsy/statsd/) usage.

Please read the [documentation](doc/toc.md).

## Utilisation de statsd

### Configuration

Il faut configurer l'extension m6_statsd en lui spécifiant la liste des serveurs utilisés, ainsi que la liste des clients.
Exemple:

    m6_statsd:
        servers:
            default:
                address:   'udp://localhost'
                port:       1234
        clients:
            default:
                servers:   ["default"]      # Permet de spécifier la liste des serveurs à utiliser pour ce client
                events:                     # Permet d'écouter des évènements "Symfony" et les retranscrire en increment statsd
                    eventName:
                        node:   statsd.node.<token>

### Utilisation

Le client par défaut est appelé de cette façon:

    $this->get('m6statsd')->increment('service.coucougraphite');

Les autres clients sont appelés de cette façon:

    $this->get('m6statsd.xxx')  // Où xxx est le nom du client
                ->decrement('service.coucougraphite')
                ->increment('service.test')
                ->timing('service.letempscdelargent', 0.2);

### Listener d'envoi

Par défaut, la méthode send() des clients est appélé à la fin du traitement d'une requête (évènement Kernel.terminate)

### Gestion automatique des noeuds

Au niveau de chaque client, on peut configurer les évènements que celui-ci écoute afin de les transformer en increment statsd.
Par exemple, en spécifiant la configuration suivante:

    clients:
        events:
            forum.read:
                increment:       minutefacile.forum.read

Lorsque l'évènement forum.read est déclenché au niveau de l'event dispatcher de Symfony, notre client statsd le capture et appel statsd en incrémentant
le noeud correspondant "minutefacile.forum.read".

Pour déclencher l'évènement sous Symfony, il suffit dans un controller de réaliser par exemple:

    $this->get('event_dispatcher')->dispatch('forum.read', new Event());

Il est également possible de gérer des tokens au sein du format des nodes statsd utilisés. La résolution finale du nom sera faite à partir des méthodes de l'objet Event reçu.
Par exemple:

    clients:
        events:
            redis.command:
                increment:       minutefacile.redis.<command>

Dans l'exemple ci-dessus, le token <command> sera remplacé par la valeur de retour à la méthode getCommand() ou la valeur de la propriété $command de l'objet Event reçu.


## Usage du composant brut ##

    use \M6Web\Component\Statsd;
    $statsd = new Statsd\Client(
        array(
            'serv1' => array('adress' => 'udp://xx.xx.xx.xx', 'port' => 'xx'),
            'serv2' => array('adress' => 'udp://xx.xx.xx.xx', 'port' => 'xx'))
    );
    $statsd->increment('service.coucougraphite');
    // on peut aussi passer un sample rate
    $statsd->decrement('service.coucougraphite')->increment('service.test')->timing('service.letempscdelargent', 0.2);
    // ..
    $statsd->send();

## Usage du service ##

### Paramétrage ###

Dans app/config/parameters.yml

### Lancement des tests unitaire ###

    ./vendor/bin/atoum -d tests

### Utilisation ###

Dans un controlleur

    $this->get('statsd')->increment('service.test');

Le send est appelé tout seul dans l'event kernel.terminate via la classe M6Web\Bundle\StatsdBundle\Statsd\Listener.php

## Todo ##
* sampler les stats directement dans les appels
* documentation à traduire en anglais
