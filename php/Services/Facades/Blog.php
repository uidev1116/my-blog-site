<?php

namespace Acms\Services\Facades;

class Blog extends Facade
{
    protected static $instance;

    /**
     * @return string
     */
    protected static function getServiceAlias()
    {
        return 'blog';
    }

    /**
     * @return bool
     */
    protected static function isCache()
    {
        return true;
    }
}