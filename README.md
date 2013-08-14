# Statds #

## utilisation de statsd

### configuration

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

### utilisation

Le client par défaut est appelé de cette façon:

    $this->get('m6statsd')->increment('service.coucougraphite');

Les autres clients sont appelés de cette façon:

    $this->get('m6statsd.xxx')  // Où xxx est le nom du client
                ->decrement('service.coucougraphite')
                ->increment('service.test')
                ->timing('service.letempscdelargent', 0.2);

### Listener d'envoie

Par défaut, la méthode send() des clients est appélé à la fin du traitement d'une requête (évènement Kernel.terminate)

### Gestion automatique des noeuds

Au niveau de chaque client, on peut configurer les évènements que celui-ci écoute afin de les transformer en increment statsd.
Par exemple, en spécifiant la configuration suivante:

    clients:
        events:
            forum.read:
                node:       minutefacile.forum.read

Lorsque l'évènement forum.read est déclenché au niveau de l'event dispatcher de Symfony, notre client statsd le capture et appelle statsd en incrémentant
le noeud correspondant "minutefacile.forum.read".

Pour déclencher l'évènement sous Symfony, il suffit dans un controller de réaliser par exemple:

    $this->get('event_dispatcher')->dispatch('forum.read', new Event());

Il est également possible de gérer des tokens au sein de format des nodes statsd utilisés. La résolution finale du nom sera faite à partir des méthodes de l'objet Event reçu.
Par exemple:

    clients:
        events:
            redis.command:
                node:       minutefacile.redis.<command>

Dans l'exemple ci-dessus, le token <command> sera remplacé par la valeur de retour à la méthode getCommand() ou la valeur de la propriété $command de l'objet Event reçu.


## usage du composant brut ##

    use \M6Web\Component\Statsd;
    $statsd = new Statsd\Client(
        array(
            'serv1' => array('adress' => 'udp://200.22.143.1', 'port' => '8125'),
            'serv2' => array('adress' => 'udp://200.22.143.2', 'port' => '8126'))
    );
    $statsd->increment('service.coucougraphite');
    // on peut passer un sample rate aussi
    $statsd->decrement('service.coucougraphite')->increment('service.test')->timing('service.letempscdelargent', 0.2);
    // ..
    $statsd->send();

## usage du service ##

### paramétrage ###

ds app/config/parameters.yml

### lancement des tests unitaire ###

    ./vendor/bin/atoum -d tests

### utilisation ###

ds un controlleur

    $this->get('statsd')->increment('service.test');

le send est appelé tout seul dans a l'event kernel.terminate via la classe M6Web\Bundle\StatsdBundle\Statsd\Listener.php

## todo ##
* sampler le stats directement ds les appels
* doc en anglais
