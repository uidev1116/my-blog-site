<?php

class ACMS_GET_Admin_Schedule_Keys extends ACMS_GET_Admin
{
    function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $DB     = DB::singleton(dsn());

        $SQL    = SQL::newSelect('schedule');
        $SQL->addWhereOpr('schedule_blog_id', BID);
        $SQL->addGroup('schedule_id');
        $all    = $DB->query($SQL->get(dsn()), 'all');

        if ( empty($all) ) {
            $Tpl->add('notFound');
            return $Tpl->get();
        }

        foreach ( $all as $row ) {
            $Tpl->add('key:loop', array('name'=>$row['schedule_name'], 'id'=>$row['schedule_id']));
        }

        return $Tpl->get();
    }
}