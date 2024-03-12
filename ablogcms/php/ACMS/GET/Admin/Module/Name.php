<?php

class ACMS_GET_Admin_Module_Name extends ACMS_GET_Admin_Module
{
    function get()
    {
        if (!$mid = idval($this->Get->get('mid'))) {
            return '';
        }

        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('module');
        $SQL->addWhereOpr('module_id', $mid);
        if (!$row = $DB->query($SQL->get(dsn()), 'row')) {
            return '';
        }

        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        $Tpl->add(null, array(
            'mid' => $mid,
            'name' => $row['module_name'],
            'label' => $row['module_label'],
            'identifier' => $row['module_identifier'],
        ));
        return $Tpl->get();
    }
}
