<?php

namespace Acms\Services\Facades;

class Auth extends Facade
{
    protected static $instance;

    /**
     * @return string
     */
    protected static function getServiceAlias()
    {
        return 'auth';
    }

    /**
     * @return bool
     */
    protected static function isCache()
    {
        return true;
    }
}