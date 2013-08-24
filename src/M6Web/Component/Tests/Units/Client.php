<?php
namespace M6Web\Component\Statsd\tests\units;

require_once __DIR__.'/../../../../../vendor/autoload.php';

use
    \M6Web\Component\Statsd,
    \mageekguy\atoum,
    mock\M6Web\Component as mock
;

/**
 * class testant le client Statsd
 */
class Client extends atoum\test
{
    /**
     * test du contructeur
     * @return [type]
     */
    public function test__construct()
    {
        $this->assert
            ->exception(function () {
                new Statsd\Client(array());
            })
            ->isInstanceOf('\M6Web\Component\Statsd\Exception')
            ->exception( function () {
                new Statsd\Client(
                    array(
                        'serv1' => array('port' => 8125)
                    )
                );
            })
            ->isInstanceOf('\M6Web\Component\Statsd\Exception')
            ->exception( function () {
                new Statsd\Client(
                    array(
                        'serv1' => array('adress' => 'udp://200.22.143.12'),
                        'serv2' => array('port' => 8125, 'adress' => 'udp://200.22.143.12')
                    )
                );
            })
            ->isInstanceOf('\M6Web\Component\Statsd\Exception')
            ->exception( function () {
                new Statsd\Client(
                    array(
                        'serv1' => array('port' => 8125, 'adress' => 'http://200.22.143.12')
                    )
                );
            })
            ->isInstanceOf('\M6Web\Component\Statsd\Exception');
    }

    /**
     * renvoi une conf de serveur
     * @return array
     */
    protected function getConf()
    {

        return array(
            'serv1' => array('adress' => 'udp://200.22.143.xxx', 'port' => '8125'),
            'serv2' => array('adress' => 'udp://200.22.143.xxx', 'port' => '8126'),
        );
    }

    /**
     * test du getServers
     * @return void
     */
    public function testGetServers()
    {
        $this->if($client = new Statsd\Client($this->getConf()))
                ->then()
                ->object($client)->isInstanceOf('\M6Web\Component\Statsd\Client')
                ->array($client->getServers())
                ->isIdenticalTo($this->getConf());
    }

    /**
     * Test get server key
     * @return void
     */
    public function testGetServerKey()
    {
        $this->if($client = new Statsd\Client($this->getConf()))
                ->then()
                ->string($client->getServerKey('foo2'))
                ->isIdenticalTo('serv1')
                ->string($client->getServerKey('foo'))
                ->isIdenticalTo('serv2');
    }

    /**
     * Test clear
     * @return void
     */
    public function testClear()
    {
        $this->if($client = new Statsd\Client($this->getConf()))
            ->then()
            ->object($client->clearToSend());
    }

    /**
     * [testTiming description]
     * @return void
     */
    public function testTiming()
    {
        $this->if($client = new Statsd\Client($this->getConf()))
            ->then()
            ->object($client->timing('service.timer.raoul',100))
            ->isInstanceOf('\M6Web\Component\Statsd\Client');
    }

    /**
     * [testIncrement description]
     * @return void
     */
    public function testIncrement()
    {
        $this->if($client = new Statsd\Client($this->getConf()))
            ->then()
            ->object($client->increment('service.raoul'))
            ->isInstanceOf('\M6Web\Component\Statsd\Client');
    }

    /**
     * Test send
     * @return void
     */
    public function testSend()
    {
        $this->if($client = new Statsd\Client($this->getConf()))
            ->then()
            ->object($client->increment('service.raoul')->decrement('service.raoul2'));

        $this->mockClass("\M6Web\Component\Statsd\Client");
        $client = new \mock\M6Web\Component\Statsd\Client($this->getConf());
        $client->getMockController()->writeDatas = function ($server, $datas) {

            return true;
        };
        $this->if($client->increment('service.foo'))
            ->then()
            ->boolean($client->send())
            ->isEqualTo(true)
            ->mock($client)
                ->call('writeDatas')->exactly(1);
        $client = new \mock\M6Web\Component\Statsd\Client($this->getConf());
        $client->getMockController()->writeDatas = function ($server, $datas) {

            return true;
        };
        $this->if($client->increment('service.foo')->increment('service.foo')) // incr x2
            ->then()
            ->boolean($client->send())
            ->mock($client)
                ->call('writeDatas')->exactly(1); // but one call
        $client = new \mock\M6Web\Component\Statsd\Client($this->getConf());
        $client->getMockController()->writeDatas = function ($server, $datas) {

            return true;
        };
        $this->if($client->increment('foo2')->increment('foo')) // incr x2
            ->then()
            ->boolean($client->send())
            ->mock($client)
                ->call('writeDatas')->exactly(2);
    }
}