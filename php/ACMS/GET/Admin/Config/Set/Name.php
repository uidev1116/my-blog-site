<?php

class ACMS_GET_Admin_Config_Set_Name extends ACMS_GET
{
    function get()
    {
        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        if  ($mid = $this->Get->get('mid')) {
            return '';
        }
        if (!$setid = idval($this->Get->get('setid'))) {
            return $Tpl->get();
        }
        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('config_set');
        $SQL->setSelect('config_set_name');
        $SQL->addWhereOpr('config_set_id', $setid);
        if (!$name = $DB->query($SQL->get(dsn()), 'one')) {
            return $Tpl->get();
        }
        $Tpl->add(null, array('setid' => $setid, 'name' => $name));
        return $Tpl->get();
    }
}
