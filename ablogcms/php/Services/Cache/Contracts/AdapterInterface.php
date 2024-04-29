<?php

namespace Acms\Services\Cache\Contracts;

interface AdapterInterface
{
    /**
     * キャッシュがあるか確認
     *
     * @param string $key
     * @return boolean
     */
    public function has($key);

    /**
     * キャッシュを取得
     *
     * @param string $key
     * @return mixed
     */
    public function get($key);

    /**
     * キャッシュを設定
     * $lifetimeを指定しない場合はデフォルト値を設定
     *
     * @param string $key
     * @param mixed $value
     * @param int $lifetime
     * @return void
     */
    public function put($key, $value, $lifetime = 0);

    /**
     * キャッシュを削除
     *
     * @param string $key
     * @return void
     */
    public function forget($key);

    /**
     * キャッシュがなかった場合はコールバックを実行し、キャッシュに追加
     * @param string $key
     * @param callable $callback
     * @param int $lifetime
     * @return void
     */
    public function remember($key, $callback, $lifetime = 0);

    /**
     * キャッシュを全削除
     * @return void
     */
    public function flush();
}
