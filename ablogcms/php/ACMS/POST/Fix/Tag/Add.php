<?php

class ACMS_POST_Fix_Tag_Add extends ACMS_POST_Fix_Tag
{
    function process($data, $word)
    {
        $DB     = DB::singleton(dsn());

        $SQL    = SQL::newInsert('tag');
        $SQL->addInsert('tag_name', $word);
        $SQL->addInsert('tag_sort', $data['tag_max']+1);
        $SQL->addInsert('tag_entry_id', $data['entry_id']);
        $SQL->addInsert('tag_blog_id', BID);
        $DB->query($SQL->get(dsn()), 'exec');
    }

    function success()
    {
        $this->Post->set('message', 'success');
    }
}
