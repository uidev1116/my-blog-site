<?php

class ACMS_GET_Shop2_Cart_Result extends ACMS_GET_Shop2_Cart_List
{
    function get()
    {	
        $this->initVars();

        $this->initPrivateVars();

        $SESSION= $this->openSession();
        $TEMP   = $SESSION->getArray('portrait_cart');
        $Tpl = $this->buildList($TEMP);

        return $Tpl;
    }

}