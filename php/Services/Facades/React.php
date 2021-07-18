<?php

namespace Acms\Services\Facades;

class React extends Facade
{
    protected static $instance;

    /**
     * @return string
     */
    protected static function getServiceAlias()
    {
        return 'react';
    }

    /**
     * @return bool
     */
    protected static function isCache()
    {
        return true;
    }
}