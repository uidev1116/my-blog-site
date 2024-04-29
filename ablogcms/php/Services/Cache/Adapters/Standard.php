<?php

namespace Acms\Services\Cache\Adapters;

use Acms\Services\Cache\Contracts\AdapterInterface;
use Symfony\Component\Cache\PruneableInterface;

class Standard implements AdapterInterface
{
    /**
     * @var \Symfony\Component\Cache\Adapter\AdapterInterface
     */
    protected $adapter;

    /**
     * Construct
     * @param \Symfony\Component\Cache\Adapter\AdapterInterface $adapter
     */
    public function __construct(\Symfony\Component\Cache\Adapter\AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * キャッシュがあるか確認
     *
     * @param string $key
     * @return boolean
     */
    public function has($key)
    {
        $item = $this->adapter->getItem($key);
        return $item->isHit();
    }

    /**
     * キャッシュを取得
     *
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        $item = $this->adapter->getItem($key);
        return $item->get();
    }

    /**
     * キャッシュを設定
     * $lifetimeを指定しない場合はデフォルト値を設定
     *
     * @param string $key
     * @param mixed $value
     * @param int $lifetime
     */
    public function put($key, $value, $lifetime = 0)
    {
        $item = $this->adapter->getItem($key);
        $item->set($value);
        if ($lifetime > 0) {
            $item->expiresAt(new \DateTime('@' . strval(REQUEST_TIME + $lifetime)));
        }
        $this->adapter->save($item);
    }

    /**
     * キャッシュを削除
     *
     * @param string $key
     */
    public function forget($key)
    {
        $this->adapter->deleteItem($key);
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
        $this->adapter->clear();
    }

    /**
     * 有効期限切れのキャッシュを削除
     */
    public function prune()
    {
        if ($this->adapter instanceof PruneableInterface) {
            $this->adapter->prune();
        }
    }
}
