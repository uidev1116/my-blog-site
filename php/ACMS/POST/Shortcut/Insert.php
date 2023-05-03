<?php

class ACMS_POST_Shortcut_Insert extends ACMS_POST_Shortcut
{
    function store($data=array())
    {
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newDelete('dashboard');
        $SQL->addWhereIn('dashboard_key', array_keys($data));
        $SQL->addWhereOpr('dashboard_blog_id', BID);
        $DB->query($SQL->get(dsn()), 'exec');

        $SQL    = SQL::newSelect('dashboard');
        $SQL->setSelect('dashboard_sort');
        $SQL->addWhereOpr('dashboard_key', 'shortcut_%', 'LIKE');
        $SQL->addWhereOpr('dashboard_blog_id', BID);
        $SQL->setOrder('dashboard_sort', 'DESC');
        $SQL->setLimit(1);
        $sort   = intval($DB->query($SQL->get(dsn()), 'one')) + 1;

        $i  = 0;
        foreach ( $data as $key => $val ) {
            $SQL    = SQL::newInsert('dashboard');
            $SQL->addInsert('dashboard_key', $key);
            $SQL->addInsert('dashboard_value', $val);
            $SQL->addInsert('dashboard_sort', $sort + $i);
            $SQL->addInsert('dashboard_blog_id', BID);
            $DB->query($SQL->get(dsn()), 'exec');
            $i++;
        }
        $this->Post->set('edit', 'insert');

        return true;
    }
}
