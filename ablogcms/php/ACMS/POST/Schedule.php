<?php

class ACMS_POST_Schedule extends ACMS_POST
{
    function post()
    {
        return $this->Post;
    }
    function buildSchedule(& $sche, & $sfds, $limit)
    {
        $invalid = false;

        for ( $i = 1; $i < $limit; $i++ ) {
            $_sche[$i] = $this->extract('schedule'.$i);
            $_sche[$i]->validate(new ACMS_Validator());
            if ( !$_sche[$i]->isValid() ) $invalid = true;

            $_sfds[$i] = $this->extract('field'.$i); 
            $_sfds[$i]->validate(new ACMS_Validator());
            if ( !$_sfds[$i]->isValid() ) $invalid = true;
        }

        if ( $invalid ) {
            return false;
        } else {
            for ( $i = 1; $i < $limit; $i++ ) {
                $schedules[$i] = $_sche[$i]->_aryField;
            }
            $sche  = serialize($schedules);

            for ( $i = 1; $i < $limit; $i++ ) {
                $sField[$i] = new Field();
                foreach ( $_sfds[$i]->_aryField as $key => $val ) {
                    $key = preg_replace('@[0-9]{1,2}$@', '', $key);
                    $sField[$i]->setField($key, $val);
                }
            }
            $sfds = serialize($sField);
        }

        return true;
    }

    function loadDefine($scid)
    {
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('schedule');
        $SQL->addWhereOpr('schedule_id', $scid);
        $SQL->addWhereOpr('schedule_year', '0000');
        $SQL->addWhereOpr('schedule_month', '00');
        $row    = $DB->query($SQL->get(dsn()), 'row');

        foreach ( $row as $key => $val ) {
            $vars[str_replace('schedule_', '', $key)] = $val;
        } 
        return $vars;
    }
}