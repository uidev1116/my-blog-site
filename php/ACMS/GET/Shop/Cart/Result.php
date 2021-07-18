<?php

class ACMS_GET_Shop_Cart_Result extends ACMS_GET_Shop_Cart_List
{
    function get()
    {	
        $this->initVars();

        $this->initPrivateVars();
        if ( !$this->detectEdition() ) return $this->LICENSE_ERR_MSG;

        $SESSION= $this->openSession();
        $TEMP   = $SESSION->getArray('portrait_cart');
        $Tpl = $this->buildList($TEMP);

        return $Tpl;
    }

}