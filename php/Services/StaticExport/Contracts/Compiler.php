<?php

namespace Acms\Services\StaticExport\Contracts;

use Acms\Services\StaticExport\Destination;

abstract class Compiler
{
    /**
     * @var \Acms\Services\StaticExport\Destination
     */
    protected $destination;

    /**
     * @var array
     */
    protected $resolver = array();

    /**
     * @param \Acms\Services\StaticExport\Destination
     */
    public function setDestination(Destination $destination)
    {
        $this->destination = $destination;
    }

    /**
     * @param string $html
     * @return string
     */
    abstract public function compile($html);
}