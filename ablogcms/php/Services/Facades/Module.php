<?php

namespace Acms\Services\Facades;

class Module extends Facade
{
    protected static $instance;

    /**
     * @return string
     */
    protected static function getServiceAlias()
    {
        return 'module';
    }

    /**
     * @return bool
     */
    protected static function isCache()
    {
        return true;
    }
}
