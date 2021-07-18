<?php

use Acms\Services\Common\InjectTemplate;

class ACMS_GET_Admin_InjectTemplate extends ACMS_GET
{
    public function get()
    {
        $type = $this->identifier;
        if (empty($type)) {
            return '';
        }

        $inject = InjectTemplate::singleton();
        $all = $inject->get($type);
        $template = '';

        foreach ($all as $item) {
            $template .= "<!--#include file=\"$item\" vars=\"\"-->\n";
        }
        if ( !$txt = spreadTemplate(resolvePath(setGlobalVars($template), config('theme'), '/')) ) {
            return '';
        }
        return $txt;
    }
}