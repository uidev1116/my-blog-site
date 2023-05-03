<?php

class ACMS_POST_Shortcut_Delete extends ACMS_POST_Shortcut
{
    function store($data=array())
    {
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newDelete('dashboard');
        $SQL->addWhereIn('dashboard_key', array_keys($data));
        $SQL->addWhereOpr('dashboard_blog_id', BID);
        $DB->query($SQL->get(dsn()), 'exec');

        $this->Post->set('edit', 'delete');

        return true;
    }
}
