<?php

namespace ShonM\ResqueBundle;

class Queue
{
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getSize()
    {
        return \Resque::size($this->name);
    }

    public function getName()
    {
        return $this->name;
    }
}
