<?php

class ACMS_GET_Shop_Cart_Empty extends ACMS_GET_Shop
{
    function get()
    {	
        $this->initVars();

        $step   = $this->Post->get('step', 'apply');
        $bid    = BID;

        if ( $step == 'result' ) {
            if ( !empty($_SESSION[$this->cname.$bid]) ) {
                unset($_SESSION[$this->cname.$bid]);
            }
        }

        return '';
    }
}