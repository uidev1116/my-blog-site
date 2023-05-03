<?php

class ACMS_POST_Media_Tags extends ACMS_POST_Media
{
    public function post()
    {
        try {
            $mid = $this->Post->get('_mid');
            $tags = $this->Post->get('_tags');
            if (!Media::validate()) {
                throw new \RuntimeException('You are not authorized to upload media.');
            }
            $this->addTag($mid, BID, $tags);
        } catch (\Exception $e) {

        }
        return $this->Post;
    }

    protected function addTag($mid, $bid, $tags)
    {
        try {
            if (empty($tags)) {
                throw new \RuntimeException('Empty tags.');
            }
            if (empty($mid) || !Media::canEdit($mid)) {
                throw new \RuntimeException('You are not authorized to upload media.');
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
                    continue;
                }
                $SQL = SQL::newInsert('media_tag');
                $SQL->addInsert('media_tag_name', $tag);
                $SQL->addInsert('media_tag_sort', $sort + 1);
                $SQL->addInsert('media_tag_media_id', $mid);
                $SQL->addInsert('media_tag_blog_id', $bid);
                DB::query($SQL->get(dsn()), 'exec');
            }
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }
}
