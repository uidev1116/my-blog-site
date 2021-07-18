<?php

class ACMS_GET_Admin_Config_Message extends ACMS_GET
{
    function get()
    {
        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        $config = $this->Post->get('config');
        
        if ( !empty($config) ) {
            $Tpl->add(null, array(
                'config_name' => $config,
            ));
        }
        
        return $Tpl->get();
    }
}
