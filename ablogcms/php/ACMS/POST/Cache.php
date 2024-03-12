<?php

class ACMS_POST_Cache extends ACMS_POST
{
    public $isCacheDelete  = false;

    function post()
    {
        if (!sessionWithCompilation()) {
            return false;
        }

        $targets = $this->Post->getArray('target');
        $this->clear($targets);

        if (HOOK_ENABLE) {
            $Hook = ACMS_Hook::singleton();
            $Hook->call('cacheClear');
            $Hook->call('cacheRefresh');
        }
        return $this->Post;
    }

    /**
     * ページキャッシュを設定に従いクリア
     *
     * @param int $eid
     */
    public static function clearPageCache($bid = BID)
    {
        $targetBlog = config('cache_clear_target', 'self');
        $pageCache = Cache::page();

        if ($targetBlog === 'self') {
            $tagBid = 'bid-' . $bid;
            $pageCache->invalidateTags([$tagBid]);
        } elseif ($targetBlog === 'all') {
            $pageCache->flush();
        } elseif ($targetBlog === 'self-descendant' || $targetBlog = 'self-ancestor') {
            $sql = SQL::newSelect('blog');
            $sql->setSelect('blog_id');
            if ($targetBlog === 'self-descendant') {
                ACMS_Filter::blogTree($sql, $bid, 'descendant-or-self');
            }
            if ($targetBlog === 'self-ancestor') {
                ACMS_Filter::blogTree($sql, $bid, 'ancestor-or-self');
            }
            $targetBlogIDs = DB::query($sql->get(dsn()), 'list');
            $tags = [];
            foreach ($targetBlogIDs as $bid) {
                $tags[] = 'bid-' . $bid;
            }
            $pageCache->invalidateTags($tags);
        }
    }

    /**
     * 指定されたエントリーのページキャッシュをクリア
     *
     * @param int $eid
     */
    public static function clearEntryPageCache($eid)
    {
        if ($eid) {
            $pageCache = Cache::page();
            $tag = 'eid-' . $eid;
            $pageCache->invalidateTags([$tag]);
        }
    }

    /**
     * 指定されたキャッシュをクリア
     *
     * @param array $targets
     */
    protected function clear(array $targets)
    {
        foreach ($targets as $target) {
            if ($target === 'page') {
                self::clearPageCache();
            } else {
                Cache::flush($target);
            }
        }
    }
}
