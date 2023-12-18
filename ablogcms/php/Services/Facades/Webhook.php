<?php

namespace Acms\Services\Facades;

class Webhook extends Facade
{
    protected static $instance;

    /**
     * @return string
     */
    protected static function getServiceAlias()
    {
        return 'webhook';
    }

    /**
     * @return bool
     */
    protected static function isCache()
    {
        return true;
    }
}
