<?php

class ACMS_GET_Admin_Layout_Add extends ACMS_GET_Admin
{
    function get()
    {
        if (!LAYOUT_EDIT) {
            return '';
        }
        if (!sessionWithAdministration()) {
            return '';
        }

        $aryTypeLabel   = [];
        $groupLabel     = [];
        $root           = 'block:loop';

        foreach (configArray('layout_add_type') as $i => $mode) {
            $label  = config('layout_add_type_label', '', $i);
            $group  = config('layout_add_type_set', '', $i);
            $aryTypeLabel[$group][] = [
                'mode'  => $mode,
                'label' => $label,
            ];
        }
        foreach (configArray('layout_group_class') as $i => $class) {
            $groupLabel[$class] = config('layout_group_label', '', $i);
        }
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        $first  = true;
        $count  = count($aryTypeLabel);
        $i      = 1;
        foreach ($aryTypeLabel as $key => $group) {
            if ($first) {
                $first = false;
                $Tpl->add(['layoutGroup#begin', $root]);
            } else {
                $Tpl->add(['layoutGroup#rear', $root]);
            }
            $groupLabelVal = isset($groupLabel[$key]) ? $groupLabel[$key] : '';
            $Tpl->add(['layoutGroup#front', $root], [
                'group'         => $key,
                'groupLabel'    => $groupLabelVal,
            ]);
            $count2 = count($group);
            foreach ($group as $j => $row) {
                $mode   = $row['mode'];
                $label  = $row['label'];

                $Tpl->add([$mode, $root]);
                $Tpl->add(['type#' . $mode, $root], [
                    'blockLabel' => $label,
                ]);

                $j++;
                if ($i >= $count && $j >= $count2) {
                    $Tpl->add(['layoutGroup#last', $root]);
                }

                $Tpl->add('block:loop', [
                    'label' => $label,
                    'classStr' => $mode,
                ]);
            }
            $i++;
        }

        $Tpl->add(['moduleLabel', $root]);
        $Tpl->add(['type#module', $root]);
        $Tpl->add($root, [
            'classStr' => 'module',
        ]);

        return $Tpl->get();
    }
}
