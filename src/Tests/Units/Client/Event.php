<?php

namespace M6Web\Bundle\StatsdBundle\Tests\Units\Client;

class Event extends \Symfony\Contracts\EventDispatcher\Event
{
    private $name = '';

    public function setName($v)
    {
        $this->name = $v;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getTiming()
    {
        return 101;
    }

    public function getMemory()
    {
        return 102;
    }
}
