<?php

namespace Acms\Services\Facades;

class Mailer extends Facade
{
    /**
     * @return string
     */
    protected static function getServiceAlias()
    {
        return 'mailer';
    }
}
