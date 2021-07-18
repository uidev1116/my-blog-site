<?php

class ACMS_GET_Links extends ACMS_GET
{
    function get()
    {
        if ( !$labels = configArray('links_label') ) return '';
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $this->buildModuleField($Tpl);
        
        $urls = configArray('links_value');
        foreach ( $labels as $i => $label ) {
            $url = isset($urls[$i]) ? $urls[$i] : '';
            $Tpl->add('loop', array(
                'url' => $url,
                'name' => $label,
            ));
        }
        return setGlobalVars($Tpl->get());
    }
}
