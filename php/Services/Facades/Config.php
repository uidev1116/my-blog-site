<?php

namespace Acms\Services\Facades;

class Config extends Facade
{
    protected static $instance;

    /**
     * @return string
     */
    protected static function getServiceAlias()
    {
        return 'config';
    }

    /**
     * @return bool
     */
    protected static function isCache()
    {
        return true;
    }
}