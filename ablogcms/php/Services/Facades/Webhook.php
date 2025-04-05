<?php

namespace Acms\Services\Facades;

/**
 * @method static void call(int $bid, string $type, array|string $events, array $args = []) Webhookを実行
 * @method static bool validateUrlScheme(string $url) URLのスキーマが http or https か確認
 * @method static bool validateUrlWhiteList(string $url) URLのホストがホワイトリストに含まれるか確認
 */
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
