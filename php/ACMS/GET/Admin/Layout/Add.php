<?php

class ACMS_GET_Admin_Layout_Add extends ACMS_GET_Admin
{
    function get()
    {
        if ( !LAYOUT_EDIT ) return '';
        if ( !sessionWithAdministration() ) return '';

        $aryTypeLabel   = array();
        $groupLabel     = array();
        $root           = 'block:loop';

        foreach ( configArray('layout_add_type') as $i => $mode ) {
            $label  = config('layout_add_type_label', '', $i);
            $group  = config('layout_add_type_set', '', $i);
            $aryTypeLabel[$group][] = array(
                'mode'  => $mode,
                'label' => $label,
            );
        }
        foreach ( configArray('layout_group_class') as $i => $class ) {
            $groupLabel[$class] = config('layout_group_label', '', $i);
        }
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        $first  = true;
        $count  = count($aryTypeLabel);
        $i      = 1;
        foreach ( $aryTypeLabel as $key => $group ) {
            if ( $first ) {
                $first = false;
                $Tpl->add(array('layoutGroup#begin', $root));
            } else {
                $Tpl->add(array('layoutGroup#rear', $root));
            }
            $groupLabelVal = isset($groupLabel[$key]) ? $groupLabel[$key] : '';
            $Tpl->add(array('layoutGroup#front', $root), array(
                'group'         => $key,
                'groupLabel'    => $groupLabelVal,
            ));
            $count2 = count($group);
            foreach ( $group as $j => $row ) {
                $mode   = $row['mode'];
                $label  = $row['label'];

                $Tpl->add(array($mode, $root));
                $Tpl->add(array('type#'.$mode, $root), array(
                    'blockLabel' => $label,
                ));

                $j++;
                if ( $i >= $count && $j >= $count2 ) {
                    $Tpl->add(array('layoutGroup#last', $root));
                }

                $Tpl->add('block:loop', array(
                    'label' => $label,
                    'class' => $mode,
                ));
            }
            $i++;
        }

        $Tpl->add(array('moduleLabel', $root));
        $Tpl->add(array('type#module', $root));
        $Tpl->add($root, array(
            'class' => 'module',
        ));

        return $Tpl->get();
    }
}
