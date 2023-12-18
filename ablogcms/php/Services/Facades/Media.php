<?php

namespace Acms\Services\Facades;

class Media extends Facade
{
    protected static $instance;

    /**
     * @return string
     */
    protected static function getServiceAlias()
    {
        return 'media';
    }

    /**
     * @return bool
     */
    protected static function isCache()
    {
        return true;
    }
}
