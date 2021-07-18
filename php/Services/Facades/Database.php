<?php

namespace Acms\Services\Facades;

class Database extends Facade
{
    protected static $instance;

    /**
     * @return string
     */
    protected static function getServiceAlias()
    {
        return 'db';
    }

    /**
     * @return bool
     */
    protected static function isCache()
    {
        return true;
    }
}