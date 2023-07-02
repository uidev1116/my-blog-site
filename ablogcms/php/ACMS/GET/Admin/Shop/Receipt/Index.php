<?php

class ACMS_GET_Admin_Shop_Receipt_Index extends ACMS_GET_Shop_Order_List
{
    function get()
    {
        if ( !(IS_LICENSED and defined('LICENSE_PLUGIN_SHOP_PRO') and !!LICENSE_PLUGIN_SHOP_PRO) ) return '';
        if ( 'shop_receipt_index' <> ADMIN and 'shop_customer_edit' <> ADMIN ) return '';
        if ( !sessionWithAdministration() ) return '';

        $this->initVars();
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        $delta  = config('admin_pager_delta');
        $attri  = config('admin_pager_attr');

        $this->receiptList($Tpl, $delta, $attri);

        return $Tpl->get();
    }
}