<?php

class ACMS_POST_Media_Tags extends ACMS_POST_Media
{
    public function post()
    {
        try {
            $mid = $this->Post->get('_mid');
            $tags = $this->Post->get('_tags');
            if (!Media::validate()) {
                AcmsLogger::info('メディア機能を使用することができませんでした');
                throw new \RuntimeException('You are not authorized to upload media.');
            }
            $this->addTag($mid, BID, $tags);

            AcmsLogger::info('メディアにタグを追加しました', [
                'mid' => $mid,
                'tags' => $tags,
            ]);
        } catch (\Exception $e) {
            AcmsLogger::info('メディアタグを保存することができませんでした', [
                'message' => $e->getMessage(),
            ]);
        }
        return $this->Post;
    }

    protected function addTag($mid, $bid, $tags)
    {
        if (empty($tags)) {
            throw new \RuntimeException('タグが指定されていません');
        }
        if (empty($mid)) {
            throw new \RuntimeException('メディアが指定されていません');
        }
        if (!Media::canEdit($mid)) {
            throw new \RuntimeException('指定されたメディアを編集できる権限がありません');
        }
        $SQL = SQL::newSelect('media_tag');
        $SQL->addSelect('media_tag_name');
        $SQL->addWhereOpr('media_tag_media_id', $mid);
        $oldTags = DB::query($SQL->get(dsn()), 'list');

        $SQL = SQL::newDelete('media_tag');
        $SQL->addWhereOpr('media_tag_media_id', $mid);
        DB::query($SQL->get(dsn()), 'exec');

        $tags = preg_split('/,/', $tags, -1, PREG_SPLIT_NO_EMPTY);
        $tags = array_merge($tags, $oldTags);
        $tags = array_unique($tags);
        foreach ($tags as $sort => $tag) {
            if (isReserved($tag)) {
                AcmsLogger::notice('「' . $tag . '」タグは、予約ワードのためメディアにタグをつけれませんでした');
                continue;
            }
            $SQL = SQL::newInsert('media_tag');
            $SQL->addInsert('media_tag_name', $tag);
            $SQL->addInsert('media_tag_sort', $sort + 1);
            $SQL->addInsert('media_tag_media_id', $mid);
            $SQL->addInsert('media_tag_blog_id', $bid);
            DB::query($SQL->get(dsn()), 'exec');
        }
        return true;
    }
}
