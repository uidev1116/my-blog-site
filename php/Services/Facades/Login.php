<?php

namespace Acms\Services\Facades;

class Login extends Facade
{
    protected static $instance;

    /**
     * @return string
     */
    protected static function getServiceAlias()
    {
        return 'login';
    }

    /**
     * @return bool
     */
    protected static function isCache()
    {
        return true;
    }
}
