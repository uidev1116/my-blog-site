<?php

namespace Acms\Services\Facades;

class Common extends Facade
{
    protected static $instance;

    /**
     * @return string
     */
    protected static function getServiceAlias()
    {
        return 'common';
    }

    /**
     * @return bool
     */
    protected static function isCache()
    {
        return true;
    }
}