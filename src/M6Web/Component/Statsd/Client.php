<?php
/**
 * @author Julien DENIAU, Alban COQUOIN
 * portage en sf php5.4 o_mansour
 *
 * usage du composant :
 * cf README.md
 *
 */
namespace M6Web\Component\Statsd;

/**
 * client Statsd
 */
class Client
{
    /**
     * les commandes à envoyer
     * @var array
     */
    protected $toSend = array();

    /**
     * les serveurs ont on va écrire
     * @var array
     */
    protected $servers = array();

    /**
     * simple comptage de $servers (pour pas le recalculer à chaque fois)
     * @var integer
     */
    private $nbServers = 0;

    /**
     * liste des clé tu tableau de serveur (pour pas la recalculer à chaque fois)
     * @var array
     */
    private $serverKeys = array();

    /**
     * contructeur
     * @param array $servers les serveurs
     */
    public function __construct(array $servers)
    {
        //$this->
        $this->init($servers);
    }

    /**
     * set the params from config
     * @param array $servers les serveurs
     *
     * @return void
     */
    protected function init(array $servers)
    {
        if (0 === count($servers)) {
            throw new Exception("dont have any servers ?");
        }
        // check server
        foreach ($servers as $serName => $server) {
            if (!isset($server['adress']) or !isset($server['port'])) {
                throw new Exception($serName." : no adress or port in the configuration ?!");
            }
            if (strpos($server['adress'], 'udp://') !== 0) {
                throw new Exception($serName." : adress should begin with udp:// ?!");
            }
            // TODO : check du format d'adresse ?
        }
        $this->servers = $servers;
        $this->nbServers = count($servers);
        $this->serverKeys = array_keys($servers);
    }

    /**
     * retourne les serveurs
     * @return array
     */
    public function getServers()
    {
        return $this->servers;
    }

    /**
     * retourne le tableau de commande à renvoyer
     * @return array
     */
    public function getToSend()
    {
        return $this->toSend;
    }

    /**
     * clear le tableau de commande à envoyer
     * @return Client
     */
    public function clearToSend()
    {
        $this->toSend = array();

        return $this;
    }

    /**
     * addToSend
     * @param string $stats      le noeud graphite
     * @param string $v          valeur
     * @param float  $sampleRate echantillonage
     * @param string $unit       unité
     *
     * @return Client
     */
    protected function addToSend($stats, $v, $sampleRate, $unit)
    {
        $this->toSend[$this->getServerKey($stats)][] = array(
            'stats'      => $stats,
            'value'      => $v,
            'sampleRate' => (float) $sampleRate,
            'unit'       => $unit
        );
    }

    /**
     * trouve un serveur en fonction de la clé
     * retour un array ('adress', 'port')
     *
     * @param string $stats service.m6replay.raoul
     *
     * @return string
     */
    public function getServerKey($stats)
    {
        return $this->serverKeys[(int) (crc32($stats) % $this->nbServers)];
        //return $this->getServers()[$this->serverKeys[(int) (crc32($stats) % $this->nbServers)]];
    }


    /**
     * Log timing information
     *
     * @param string $stats      The metric to in log timing info for.
     * @param float  $time       The ellapsed time (ms) to log
     * @param float  $sampleRate the rate (0-1) for sampling.
     *
     * @return Client
     */
    public function timing($stats, $time, $sampleRate = 1)
    {
        $this->addToSend($stats, $time, $sampleRate, 'ms');

        return $this;
        //self::$STATSD_SEND[(int) (crc32($stats) % 4)][] = array($stats, $time, $sampleRate, 'ms');
    }


    /**
     * Increments one or more stats counters
     *
     * @param string  $stats      The metric(s) to increment.
     * @param float|1 $sampleRate the rate (0-1) for sampling.
     *
     * @return Client
     */
    public function increment($stats, $sampleRate = 1)
    {
        //self::$STATSD_SEND[(int) (crc32($stats) % 4)][] = array($stats, 1, $sampleRate, 'c');
        $this->addToSend($stats, '1', $sampleRate, 'c');

        return $this;
    }


     /**
     * Decrements one or more stats counters.
     *
     * @param string $stats      The metric(s) to decrement.
     * @param float  $sampleRate the rate (0-1) for sampling.
     *
     * @return Client
     */
    public function decrement($stats, $sampleRate = 1)
    {
        $this->addToSend($stats, '-1', $sampleRate, 'c');

        return $this;
        //self::$STATSD_SEND[(int) (crc32($stats) % 4)][] = array($stats, -1, $sampleRate, 'c');
    }

    /**
     * Squirt the metrics over UDP
     * return always true
     * clear the ToSend datas weanwhile
     *
     * @return bool
     **/
    public function send()
    {
        // build sampledata
        $sampledData = array();
        foreach ($this->getToSend() as $server => $arrayToSend) {
            foreach ($arrayToSend as $data) {
                if ($data['sampleRate'] < 1) {
                    if ((mt_rand() / mt_getrandmax()) <= $data['sampleRate']) {
                        $sampledData[$server][] = $data['stats'].':'.$data['value'].'|'.$data['unit'].'|@'.$data[2];
                    }
                } else {
                    $sampledData[$server][] = $data['stats'].':'.$data['value'].'|'.$data['unit'];
                }
            }
        }
        // clear data to send
        $this->clearToSend();
        if (0 == count($sampledData)) {
            // rien à retourner
            return true;
        }
        // pour chaque server
        foreach ($sampledData as $server => $data) {
            $dataLength = max(1, round(count($data) / 30)); // Divide string for max 1472 octects packet sended to statsD dram (28 for headers out-in)
            for ($i = 0; $i < $dataLength; $i++) {
                $datas = array_slice($data, $i * 30, 30);
                $this->writeDatas($server, $datas);
            }
        }

        return true;
    }

    /**
     * écriture de paquet de data
     * TODO cette fonction est publique pour les tests ... je n'arrive pas à la mocker autrement
     *
     *
     * @param string $server server key
     * @param array  $datas  array de data à env
     *
     * @return bool
     */
    public function writeDatas($server, $datas)
    {
        if (!isset($this->getServers()[$server])) {
            throw new Exception($server." undefined in the configuration");
        }
        $s = $this->getServers()[$server];
        $fp = fsockopen($s['adress'], $s['port']);
        if ($fp !== false) {
            foreach ($datas as $value) {
                // write packets
                if (!@fwrite($fp, $value)) {
                    return false;
                }
            }
            // close conn
            if (!fclose($fp)) {
                return false;
            }
        } else {
            return false;
        }

        return true;
    }
}
