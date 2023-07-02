<?php

namespace Acms\Services\Facades;

class Approval extends Facade
{
    protected static $instance;

    /**
     * @return string
     */
    protected static function getServiceAlias()
    {
        return 'approval';
    }

    /**
     * @return bool
     */
    protected static function isCache()
    {
        return true;
    }
}