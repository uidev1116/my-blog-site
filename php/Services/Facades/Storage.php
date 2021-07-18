<?php

namespace Acms\Services\Facades;

class Storage extends Facade
{
    protected static $instance;

    /**
     * @return string
     */
    protected static function getServiceAlias()
    {
        return 'storage';
    }

    /**
     * @return bool
     */
    protected static function isCache()
    {
        return true;
    }
}