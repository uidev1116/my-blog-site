<?php

class ACMS_GET_Admin_Module_Template extends ACMS_GET_Admin_Edit
{
    function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $mid    = $this->Get->get('mid');

        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('module');
        $SQL->addWhereOpr('module_id', $mid);
        $module = $DB->query($SQL->get(dsn()), 'row');

        $themes         = array();
        $theme          = config('theme');
        $tplModuleDir   = 'include/module/template/';
        while (!empty($theme)) {
            array_unshift($themes, $theme);
            $theme  = preg_replace('/^[^@]*?(@|$)/', '', $theme);
        }
        array_unshift($themes, 'system');

        $name       = $module['module_name'];
        $identifier = $module['module_identifier'];

        //---------------
        // layout module
        $tplAry     = array();
        $tplLabels  = array();
        $fix        = false;
        foreach ($themes as $theme) {
            $dir = SCRIPT_DIR . THEMES_DIR . $theme . '/' . $tplModuleDir . $name . '/';
            if (Storage::isDirectory($dir)) {
                $templateDir    = opendir($dir);
                while ($tpl = readdir($templateDir)) {
                    preg_match('/(?:.*)\/(.*)(?:\.([^.]+$))/', $dir . $tpl, $info);
                    if (!isset($info[1]) || !isset($info[2])) {
                        continue;
                    }
                    $pattern = '/^(' . $info[1] . '|' . $info[1] . config('module_identifier_duplicate_suffix') . '.*)$/';
                    if (preg_match($pattern, $identifier)) {
                        $tplAry = array();
                        $fix    = true;
                        break;
                    }
                    if (
                        0
                        || strncasecmp($tpl, '.', 1) === 0
                        || $info[2] === 'yaml'
                    ) {
                        continue;
                    }
                    $tplAry[] = $tpl;
                }
                if ($labelAry = Config::yamlLoad($dir . 'label.yaml')) {
                    $tplLabels += $labelAry;
                }
            }
        }
        $tplAry = array_unique($tplAry);
        $type   = 'array';

        $tplSort = array();
        foreach ($tplLabels as $tpl => $label) {
            $key = array_search($tpl, $tplAry, true);
            if ($key !== false) {
                $tplSort[] = array(
                    'template' => $tpl,
                    'tplLabel' => $label,
                );
                unset($tplAry[$key]);
            }
        }
        foreach ($tplAry as $tpl) {
            $tplSort[] = array(
                'template' => $tpl,
                'tplLabel' => $tpl,
            );
        }
        foreach ($tplSort as $i => $loop) {
            if ($i < count($tplSort) - 1) {
                $Tpl->add(array('glue', 'template:loop'));
            }
            $Tpl->add('template:loop', $loop);
        }
        if (empty($tplSort)) {
            if ($fix) {
                $Tpl->add(array('fixTmpl', 'module:loop'));
                $type   = 'fix';
            } else {
                $Tpl->add(array('notEmptyTmpl', 'module:loop'));
                $type   = 'empty';
            }
        }

        $Tpl->add(null, array(
            'type'  => $type,
        ));

        return $Tpl->get();
    }
}
