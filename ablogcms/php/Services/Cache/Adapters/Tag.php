<?php

namespace Acms\Services\Cache\Adapters;

class Tag extends Standard
{
    /**
     * キャッシュを設定
     * $lifetimeを指定しない場合はデフォルト値を設定
     *
     * @param string $key
     * @param any $value
     * @param array $tags
     * @param int $lifetime
     */
    public function put($key, $value, $lifetime = 0, $tags = [])
    {
        $item = $this->adapter->getItem($key);
        $item->set($value);
        if ($lifetime > 0) {
            $item->expiresAt(new \DateTime('@' . strval(REQUEST_TIME + $lifetime)));
        }
        foreach ($tags as $tag) {
            $item->tag($tag);
        }
        $this->adapter->save($item);
    }

    /**
     * タグを指定してキャッシュ削除
     */
    public function invalidateTags($tags = [])
    {
        $this->adapter->invalidateTags($tags);
    }
}
