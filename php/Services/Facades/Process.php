<?php

namespace Acms\Services\Facades;

class Process extends Facade
{
    protected static $instance;

    /**
     * @return string
     */
    protected static function getServiceAlias()
    {
        return 'process';
    }

    /**
     * @return bool
     */
    protected static function isCache()
    {
        return true;
    }
}