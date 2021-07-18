<?php

class ACMS_GET_Admin_Import_Index extends ACMS_GET_Admin
{
    function get()
    {
        if ( 'import_index' <> ADMIN ) return false;
        if ( !sessionWithAdministration() ) return false;

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        
        $aryAdmin   = array(
            'WordPress'    => 'import_wordpress',
            'Movable Type' => 'import_mt',
            'CSV'          => 'import_csv',
        );
        if ( LICENSE_BLOG_LIMIT == UNLIMITED_NUMBER_OF_USERS ) {
            $aryAdmin['USER']   = 'import_user';
        }

        foreach ( $aryAdmin as $label => $admin ) {
            $AP     = array(
                'bid'   => BID,
                'admin' => $admin,
            );
            
            $Tpl->add('type:loop', array(
                'url'   => acmsLink($AP),
                'label' => $label,
            ));
        }

        return $Tpl->get();
    }
}
