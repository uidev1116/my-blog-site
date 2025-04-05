<?php

namespace Acms\Services\Facades;

/**
 * @method static \Acms\Services\Session\Engine handle() セッションを管理
 * @method static string getSessionId() セッションIDを取得
 * @method static void writeClose() セッションを閉じる
 * @method static void regenerate() セッションを再生成
 * @method static void save() セッションを保存
 * @method static mixed get(string $key) セッションの値を取得
 * @method static void set(string $key, mixed $val) セッションの値を設定
 * @method static void delete(string $key) セッションの値を削除
 * @method static void clear() セッションをクリア
 * @method static void destroy() セッションを破棄
 * @method static void extendExpires() セッションの有効期限を延長
 */
class Session extends Facade
{
    protected static $instance;

    /**
     * @return string
     */
    protected static function getServiceAlias()
    {
        return 'session';
    }

    /**
     * @return bool
     */
    protected static function isCache()
    {
        return true;
    }
}
