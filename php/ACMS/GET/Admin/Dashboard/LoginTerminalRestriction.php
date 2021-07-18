<?php

class ACMS_GET_Admin_Dashboard_LoginTerminalRestriction extends ACMS_GET
{
    function get()
    {
        if ( !sessionWithAdministration() ) return '';
        if ( RBID !== BID ) return '';

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        if ( config('login_terminal_restriction') === sha1('permission'.UA) ) {
            $Tpl->add('status#permission');
            $Tpl->add('status2#permission');
        } else {
            $Tpl->add('status#denial');
            $Tpl->add('status2#denial');
        }
        return $Tpl->get();
    }
}