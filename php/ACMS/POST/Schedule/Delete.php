<?php

class ACMS_POST_Schedule_Delete extends ACMS_POST_Schedule
{
    function post()
    {
        if (!sessionWithScheduleAdministration()) {
            return $this->Post;
        }

        $DB     = DB::singleton(dsn());
        $scid   = $this->Get->get('scid');

        $SQL    = SQL::newDelete('schedule');
        $SQL->addWhereOpr('schedule_id', $scid);
        $SQL->addWhereOpr('schedule_blog_id', BID);
        $DB->query($SQL->get(dsn()), 'exec');

        $SQL    = SQL::newDelete('config');
        $SQL->addWhereOpr('config_key', 'schedule_label@'.$scid);
        $SQL->addWhereOpr('config_blog_id', BID);
        $DB->query($SQL->get(dsn()), 'exec');

        Config::forgetCache(BID);

        $this->Post->set('edit', 'delete');

        return $this->Post;
    }
}
