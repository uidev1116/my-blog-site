<?php

namespace Acms\Services\Facades;

class Entry extends Facade
{
    protected static $instance;

    /**
     * @return string
     */
    protected static function getServiceAlias()
    {
        return 'entry';
    }

    /**
     * @return bool
     */
    protected static function isCache()
    {
        return true;
    }
}