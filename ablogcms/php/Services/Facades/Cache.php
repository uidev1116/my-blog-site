<?php

namespace Acms\Services\Facades;

/**
 * Class Cache
 *
 * @method static void flush(string $type) タイプ別のキャッシュを削除する
 * @method static void allFlush() すべてのキャッシュを削除する
 * @method static void prune(string $type) タイプ別の有効期限切れキャッシュを削除する
 * @method static void allPrune() すべての有効期限切れキャッシュを削除する
 * @method static \Acms\Services\Cache\Contracts\AdapterInterface template() テンプレート用キャッシュ
 * @method static \Acms\Services\Cache\Contracts\AdapterInterface field() フィールド用キャッシュ
 * @method static \Acms\Services\Cache\Contracts\AdapterInterface temp() 一時的に使えるキャッシュ
 * @method static \Acms\Services\Cache\Contracts\AdapterInterface module() モジュール用キャッシュ
 * @method static \Acms\Services\Cache\Contracts\AdapterInterface config() 設定用キャッシュ
 * @method static \Acms\Services\Cache\Contracts\AdapterInterface page() ページ用キャッシュ
 */
class Cache extends Facade
{
    protected static $instance;

    /**
     * @return string
     */
    protected static function getServiceAlias()
    {
        return 'cache';
    }

    /**
     * @return bool
     */
    protected static function isCache()
    {
        return true;
    }
}
