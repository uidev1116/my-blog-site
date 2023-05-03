<?php

class ACMS_POST_Schedule_EditLabel extends ACMS_POST
{
    function post()
    {
        $scid   = $this->Get->get('scid');
        $Config = $this->extract('schedule');
        $Config->setMethod('schedule', 'operative', sessionWithScheduleAdministration());
        $Config->validate(new ACMS_Validator());

        if ( !$Config->isValid() ) {
            return $this->Post;
        }

        $sort   = $Config->getArray('schedule_label_sort');
        $name   = $Config->getArray('schedule_label_name');
        $class  = $Config->getArray('schedule_label_class');
        $key    = $Config->getArray('schedule_label_key');

        $rows   = count($name);
        $fds    = array();

        /**
         * build Labels
         */
        for ( $i=0; $i<$rows; $i++ ) {
            if ( empty($name[$i]) ) continue;
            if ( empty($key[$i]) )  $key[$i] = uniqueString();

            $_tmp   = array($name[$i], $key[$i]);
            if ( !empty($class[$i]) ) $_tmp[] = $class[$i];

            $fds[implode(config('schedule_label_separator'), $_tmp)] = $sort[$i];

            unset($_tmp);
        }

        /**
         * save config
         */

        // database
        $DB     = DB::singleton(dsn());

        // delete
        $SQL    = SQL::newDelete('config');
        $SQL->addWhereOpr('config_key', 'schedule_label@'.$scid);
        $SQL->addWhereOpr('config_blog_id', BID);
        $DB->query($SQL->get(dsn()), 'exec');

        Config::forgetCache(BID);

        // insert
        asort($fds);
        $cnt    = 1;
        $Config =& Field::singleton('config');
        $Config->delete('schedule_label@'.$scid);

        foreach ( $fds as $label => $num ) {
            $SQL    = SQL::newInsert('config');
            $SQL->addInsert('config_key', 'schedule_label@'.$scid);
            $SQL->addInsert('config_value', $label);
            $SQL->addInsert('config_sort', $cnt++);
            $SQL->addInsert('config_blog_id', BID);
            $DB->query($SQL->get(dsn()), 'exec');
            $Config->add('schedule_label@'.$scid, $label);
        }
        Config::forgetCache(BID);
        $this->Post->set('edit', 'update');

        return $this->Post;
    }
}
