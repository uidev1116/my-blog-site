<?php

class ACMS_POST_Cache_Save extends ACMS_POST_Config
{
    function post()
    {
        if ( !IS_LICENSED ) return false;
        if ( !sessionWithAdministration() ) return false;

        $Config = $this->extract('config');
        $Config = Config::setValide($Config);

        $Config->validate(new ACMS_Validator());

        if ( $this->Post->isValidAll() ) {
            Config::saveConfig($Config, BID, $rid, $mid);
            $this->Post->set('notice_mess', 'show');
        }

        return $this->Post;
    }
}
