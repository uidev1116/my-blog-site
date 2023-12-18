<?php

class ACMS_GET_Admin_Shop_Receipt_Detail extends ACMS_GET_Shop_Order_Detail
{
    function get()
    {
        if ( !sessionWithAdministration() ) return '';

        $this->initVars();
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        $this->receiptDetail($Tpl);

        return $Tpl->get();
    }
}