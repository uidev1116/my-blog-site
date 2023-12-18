<?php

namespace Acms\Services\Facades;

class Template extends Facade
{
    protected static $instance;

    /**
     * @return string
     */
    protected static function getServiceAlias()
    {
        return 'template';
    }

    /**
     * @return bool
     */
    protected static function isCache()
    {
        return true;
    }
}