<?php

namespace Acms\Services\Facades;

class Application extends Facade
{
    protected static $instance;

    /**
     * @return string
     */
    protected static function getServiceAlias()
    {
        return 'application';
    }

    /**
     * @return bool
     */
    protected static function isCache()
    {
        return true;
    }
}
