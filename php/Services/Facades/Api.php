<?php

namespace Acms\Services\Facades;

class Api extends Facade
{
    protected static $instance;

    /**
     * @return string
     */
    protected static function getServiceAlias()
    {
        return 'api-get';
    }

    /**
     * @return bool
     */
    protected static function isCache()
    {
        return true;
    }
}
