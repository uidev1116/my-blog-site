<?php

namespace Acms\Services\Facades;

class RichEditor extends Facade
{
    protected static $instance;

    /**
     * @return string
     */
    protected static function getServiceAlias()
    {
        return 'rich-editor';
    }

    /**
     * @return bool
     */
    protected static function isCache()
    {
        return true;
    }
}