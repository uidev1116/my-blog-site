<?php

namespace Acms\Services\Facades;

class Http extends Facade
{
    protected static $instance;

    /**
     * @return string
     */
    protected static function getServiceAlias()
    {
        return 'http';
    }

    /**
     * @return bool
     */
    protected static function isCache()
    {
        return true;
    }
}