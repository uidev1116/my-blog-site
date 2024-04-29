<?php

class ACMS_GET_Admin_Dashboard_ThemeChanger extends ACMS_GET
{
    function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $vars   = [];

        $thmPath = SCRIPT_DIR . THEMES_DIR;
        $curThm = config('theme');

        if (Storage::isDirectory($thmPath)) {
            $dh = opendir($thmPath);
            while (false != ($dir = readdir($dh))) {
                $vars = ['theme' => $dir, 'label' => $dir];

                if (!Storage::isDirectory($thmPath . $dir)) {
                    continue;
                } elseif ($dir == 'system') {
                    continue;
                } elseif ($dir == '.' || $dir == '..') {
                    continue;
                } elseif ($dir == $curThm) {
                    $vars['selected'] = 'selected="selected"';
                }

                $Tpl->add('theme:loop', $vars);
            }
        }

        return $Tpl->get();
    }
}
