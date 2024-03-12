<?php

namespace Acms\Services\Facades;

class Cache extends Facade
{
    protected static $instance;

    /**
     * @return string
     */
    protected static function getServiceAlias()
    {
        return 'cache';
    }

    /**
     * @return bool
     */
    protected static function isCache()
    {
        return true;
    }
}
