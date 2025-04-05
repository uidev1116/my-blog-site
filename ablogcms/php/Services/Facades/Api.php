<?php

namespace Acms\Services\Facades;

/**
 * Class Api
 *
 * @method static never get(string $identifier) モジュールIDのデータをJSON形式で出力する
 */
class Api extends Facade
{
    protected static $instance;

    /**
     * @return string
     */
    protected static function getServiceAlias()
    {
        return 'api-get';
    }

    /**
     * @return bool
     */
    protected static function isCache()
    {
        return true;
    }
}
