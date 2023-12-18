<?php

namespace Acms\Services\Cache\Adapters;

use Acms\Services\Cache\Contracts\AdapterInterface;

class NoCache implements AdapterInterface
{
    /**
     * キャッシュがあるか確認
     *
     * @param string $key
     * @return boolean
     */
    public function has($key)
    {
        return false;
    }

    /**
     * キャッシュを取得
     *
     * @param string $key
     * @return any
     */
    public function get($key)
    {
        return false;
    }

    /**
     * キャッシュを設定
     * $lifetimeを指定しない場合はデフォルト値を設定
     *
     * @param string $key
     * @param $value
     * @param int $lifetime
     */
    public function put($key, $value, $lifetime = 0)
    {

    }

    /**
     * キャッシュを削除
     *
     * @param string $key
     */
    public function forget($key)
    {

    }

    /**
     * キャッシュがなかった場合はコールバックを実行し、キャッシュに追加
     */
    public function remember($key, $callback, $lifetime = 0)
    {

    }

    /**
     * キャッシュを全削除
     */
    public function flush()
    {

    }

    /**
     * 有効期限切れのキャッシュを削除
     */
    public function prune()
    {

    }

    /**
     * タグを指定してキャッシュ削除
     */
    public function invalidateTags($tags = [])
    {

    }
}
