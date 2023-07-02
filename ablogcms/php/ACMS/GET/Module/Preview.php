<?php

class ACMS_GET_Module_Preview extends ACMS_GET_Layout
{
    function get()
    {
        $DB     = DB::singleton(dsn());
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        $mid    = $this->Get->get('mid');
        $tpl    = $this->Get->get('tpl');

        $SQL    = SQL::newSelect('module');
        $SQL->addSelect('module_id');
        $SQL->addSelect('module_identifier');
        $SQL->addSelect('module_name');
        $SQL->addWhereOpr('module_id', $mid);
        $module = $DB->query($SQL->get(dsn()), 'row');

        $html   = $this->spreadModule($module['module_name'], $module['module_identifier'], $tpl);
        $Tpl->add(null, array(
            'html'  => buildIF($html),
        ));

        return $Tpl->get();
    }
}
