<?php

class ACMS_POST_Schedule_Insert extends ACMS_POST_Schedule
{
    function post()
    {
        $Conf = $this->extract('schedule');
        $Conf->setMethod('name', 'required');
        $Conf->setMethod('schedule', 'operative', sessionWithScheduleAdministration());
        $Conf->validate(new ACMS_Validator());

        if ( !$Conf->isValid() ) {
            $this->Post->set('step', 'reapply');
            return $this->Post;
        }

        // insert
        $DB     = DB::singleton(dsn());
        $scid   = $DB->query(SQL::nextval('schedule_id', dsn()), 'seq');
        $SQL    = SQL::newInsert('schedule');
        $SQL->addInsert('schedule_id', $scid);
        $SQL->addInsert('schedule_name', $Conf->get('name'));
        $SQL->addInsert('schedule_desc', $Conf->get('desc'));
        $SQL->addInsert('schedule_year', '0000');
        $SQL->addInsert('schedule_month', '00');
        $SQL->addInsert('schedule_data', '');
        $SQL->addInsert('schedule_field', '');
        $SQL->addInsert('schedule_blog_id', BID);
        $DB->query($SQL->get(dsn()), 'exec');

        $this->Post->set('edit', 'insert');

        return $this->Post;
    }
}
