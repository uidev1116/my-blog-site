<?php

class ACMS_POST_Schedule_EditData extends ACMS_POST_Schedule
{
    function post()
    {
        $Conf = $this->extract('schedule');
        $Conf->setMethod('year',    'regex', '@^[0-9]{4}$@');
        $Conf->setMethod('month',   'regex', '@^[0-9]{2}$@');
        $Conf->setMethod('schedule', 'operative', sessionWithScheduleAdministration());
        $Conf->validate(new ACMS_Validator());

        $year   = $Conf->get('year');
        $month  = $Conf->get('month');
        $limit  = date('t', mktime(0,0,0,$month,1,$year)) + 1;

        $sche   = array();
        $sfds   = array();

        $build  = $this->buildSchedule($sche, $sfds, $limit);

        // validation result & serialize
        if ( !$Conf->isValid() || $build == false ) {
            $this->Post->set('step', 'reapply');
            $this->Post->set('reapply', array('data'=> @unserialize($sche), 'field'=>@unserialize($sfds)));
            return $this->Post;
        }

        $DB     = DB::singleton(dsn());
        $scid   = $this->Get->get('scid');

        // delete
        $SQL    = SQL::newDelete('schedule');
        $SQL->addWhereOpr('schedule_id', $scid);
        $SQL->addWhereOpr('schedule_year', $Conf->get('year'));
        $SQL->addWhereOpr('schedule_month', $Conf->get('month'));
        $SQL->addWhereOpr('schedule_blog_id', BID);
        $DB->query($SQL->get(dsn()), 'exec');

        // load define
        $define = $this->loadDefine($scid);

        // insert
        $SQL    = SQL::newInsert('schedule');
        $SQL->addInsert('schedule_id', $scid);
        $SQL->addInsert('schedule_name', $define['name']);
        $SQL->addInsert('schedule_desc', $define['desc']);
        $SQL->addInsert('schedule_year', $Conf->get('year'));
        $SQL->addInsert('schedule_month', $Conf->get('month'));
        $SQL->addInsert('schedule_data', $sche);
        $SQL->addInsert('schedule_field', $sfds);
        $SQL->addInsert('schedule_blog_id', BID);
        $DB->query($SQL->get(dsn()), 'exec');

        $this->Post->set('edit', 'update');

        return $this->Post;
    }
}
