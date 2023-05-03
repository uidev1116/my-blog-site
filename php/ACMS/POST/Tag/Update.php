<?php

class ACMS_POST_Tag_Update extends ACMS_POST
{
    public function post()
    {
        $tag = $this->Post->get('tag');

        $this->Post->setMethod('tag', 'required');

        if (roleAvailableUser()) {
            $this->Post->setMethod(
                'tag',
                'operable',
                !!$this->Q->get('tag') && roleAuthorization('tag_edit', BID)
            );
        } else {
            $this->Post->setMethod(
                'tag',
                'operable',
                !!$this->Q->get('tag') && sessionWithCompilation()
            );
        }

        $this->Post->setMeta('old_tag', 'required');
        if (isReserved($tag)) {
            $this->Post->setMethod('tag', 'reserved', false);
        }
        if (!preg_match(REGEX_INVALID_TAG_NAME, $tag)) {
            $this->Post->setMethod('tag', 'string', false);
        }

        $this->Post->validate(new ACMS_Validator());

        if (!$this->Post->isValidAll()) {
            return $this->Post;
        }

        $oldTag = $this->Post->get('old_tag');
        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('tag');
        $SQL->setSelect('tag_entry_id');
        $SQL->addWhereIn('tag_name', array($oldTag, $tag));
        $SQL->addWhereOpr('tag_blog_id', BID);
        $SQL->setGroup('tag_entry_id');
        $SQL->setHaving(SQL::newOpr('tag_entry_id', 2, '>=', null, 'COUNT'));
        $q = $SQL->get(dsn());

        if ($DB->query($q, 'fetch') && ($row = $DB->fetch($q))) {
            do {
                $eid = intval($row['tag_entry_id']);
                $Del = SQL::newDelete('tag');
                $Del->addWhereOpr('tag_name', $tag);
                $Del->addWhereOpr('tag_entry_id', $eid);
                $Del->addWhereOpr('tag_blog_id', BID);
                $DB->query($Del->get(dsn()), 'exec');
            } while ($row = $DB->fetch($q));
        }

        $SQL = SQL::newUpdate('tag');
        $SQL->setUpdate('tag_name', $tag);
        $SQL->addWhereOpr('tag_name', $oldTag);
        $SQL->addWhereOpr('tag_blog_id', BID);
        $DB->query($SQL->get(dsn()), 'exec');

        $this->Post->set('edit', 'update');
        return $this->Post;
    }
}
