<?php

class ACMS_GET_Admin_Config_LayoutGroup extends ACMS_GET_Admin_Config
{
    function get()
    {
        if ( !IS_LICENSED ) { return ''; }
        if ( !($rid = intval($this->Get->get('rid'))) ) { $rid = null; }
        if ( !($mid = intval($this->Get->get('mid'))) ) { $mid = null; }

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $Config =& $this->getConfig($rid, $mid);

        $groups = $Config->getArray('layout_group_class');
        $labels = $Config->getArray('layout_group_label');
        $hoge   = $Config->getArray('layout_add_type_set');

        $vars   = array();
        foreach ( $groups as $i => $group ) {
            $label = $labels[$i];
            if ( empty($group) || empty($label) ) {
                continue;
            }
            $Tpl->add('group:loop', array(
                'group' => $group,
                'label' => $label,
            ));
        }

        $Tpl->add(null, $vars);

        return $Tpl->get();
    }
}
