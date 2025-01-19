<?php

namespace Acms\Services\Cache\Adapters;

use Acms\Services\Cache\Contracts\AdapterInterface;
use Symfony\Component\Cache\CacheItem;
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
     * キャッシュアイテムの取得
     * @param string $key
     * @return CacheItem
     */
    public function getItem(string $key): CacheItem
    {
        return $this->adapter->getItem($key);
    }

    /**
     * キャッシュがあるか確認
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
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
    public function get(string $key)
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
     * @return void
     */
    public function put(string $key, $value, int $lifetime = 0): void
    {
        $item = $this->adapter->getItem($key);
        $item->set($value);
        $this->putItem($item, $lifetime);
    }

    /**
     * キャッシュアイテムを設定
     * $lifetimeを指定しない場合はデフォルト値を設定
     *
     * @param CacheItem $item
     * @param int $lifetime
     * @return void
     */
    public function putItem(CacheItem $item, int $lifetime = 0): void
    {
        if ($lifetime > 0) {
            $item->expiresAt(new \DateTime('@' . strval(REQUEST_TIME + $lifetime)));
        }
        $this->adapter->save($item);
    }

    /**
     * キャッシュを削除
     *
     * @param string $key
     * @return void
     */
    public function forget(string $key): void
    {
        $this->adapter->deleteItem($key);
    }

    /**
     * キャッシュがなかった場合はコールバックを実行し、キャッシュに追加
     * @param string $key
     * @param callable $callback
     * @param int $lifetime
     * @return void
     */
    public function remember(string $key, $callback, int $lifetime = 0): void
    {
    }

    /**
     * キャッシュを全削除
     * @return void
     */
    public function flush(): void
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
