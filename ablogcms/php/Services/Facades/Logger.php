<?php

namespace Acms\Services\Facades;

class Logger extends Facade
{
    protected static $instance;

    /**
     * @return string
     */
    protected static function getServiceAlias()
    {
        return 'acms-logger';
    }

    /**
     * @return bool
     */
    protected static function isCache()
    {
        return true;
    }
}
