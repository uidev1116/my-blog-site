<?php

class ACMS_POST_Shortcut_Update extends ACMS_POST_Shortcut
{
    function store($data=array())
    {
        $DB     = DB::singleton(dsn());
        foreach ( $data as $key => $val ) {
            $SQL    = SQL::newUpdate('dashboard');
            $SQL->setUpdate('dashboard_value', $val);
            $SQL->addWhereOpr('dashboard_key', $key);
            $SQL->addWhereOpr('dashboard_blog_id', BID);
            $DB->query($SQL->get(dsn()), 'exec');
        }
        $this->Post->set('edit', 'update');

        return true;
    }
}
