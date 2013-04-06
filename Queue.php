<?php

namespace ShonM\ResqueBundle;

use Resque\Resque as BaseResque;

class Queue
{
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getSize()
    {
        return BaseResque::size($this->name);
    }

    public function getName()
    {
        return $this->name;
    }
}
