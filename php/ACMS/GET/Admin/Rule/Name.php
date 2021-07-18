<?php

class ACMS_GET_Admin_Rule_Name extends ACMS_GET
{
    function get()
    {
        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        if (!$rid = idval($this->Get->get('rid'))) {
            return $Tpl->get();
        }
        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('rule');
        $SQL->setSelect('rule_name');
        $SQL->addWhereOpr('rule_id', $rid);
        if (!$name = $DB->query($SQL->get(dsn()), 'one')) {
            return $Tpl->get();
        }

        $Tpl->add(null, array('rid' => $rid, 'name' => $name));
        return $Tpl->get();
    }
}
