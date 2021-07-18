<?php

namespace Acms\Services\Facades;

class Tfa extends Facade
{
    protected static $instance;

    /**
     * @return string
     */
    protected static function getServiceAlias()
    {
        return 'login.tfa';
    }

    /**
     * @return bool
     */
    protected static function isCache()
    {
        return true;
    }
}
