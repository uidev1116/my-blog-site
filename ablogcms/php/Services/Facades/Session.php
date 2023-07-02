<?php

namespace Acms\Services\Facades;

class Session extends Facade
{
    protected static $instance;

    /**
     * @return string
     */
    protected static function getServiceAlias()
    {
        return 'session';
    }

    /**
     * @return bool
     */
    protected static function isCache()
    {
        return true;
    }
}