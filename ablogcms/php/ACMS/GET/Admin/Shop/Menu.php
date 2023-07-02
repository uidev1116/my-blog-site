<?php

class ACMS_GET_Admin_Shop_Menu extends ACMS_GET_Admin
{
    function get()
    {
        if ( !(IS_LICENSED and defined('LICENSE_PLUGIN_SHOP_PRO') and !!LICENSE_PLUGIN_SHOP_PRO) ) return '';
        if ( 'shop_menu' <> ADMIN ) return '';
        if ( !sessionWithAdministration() ) return '';

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        if ( config('shop') == 'on' ) {
            $block  = 'enable';

            $Tpl->add(array('shop_setting_order', $block), array(
                'url' => acmsLink(array('admin' => 'shop_setting_order', 'bid' => BID))
            ));
            $Tpl->add(array('shop_setting_calc', $block), array(
                'url' => acmsLink(array('admin' => 'shop_setting_calc', 'bid' => BID))
            ));
            $Tpl->add(array('shop_setting_shipping', $block), array(
                'url' => acmsLink(array('admin' => 'shop_setting_shipping', 'bid' => BID))
            ));
            $Tpl->add(array('shop_setting_external', $block), array(
                'url' => acmsLink(array('admin' => 'shop_setting_external', 'bid' => BID))
            ));
            $Tpl->add(array('shop_setting_misc', $block), array(
                'url' => acmsLink(array('admin' => 'shop_setting_misc', 'bid' => BID))
            ));
    
            $Tpl->add(array('shop_customer_index', $block), array(
                'url' => acmsLink(array('admin' => 'shop_customer_index', 'bid' => BID))
            ));
            
            $Tpl->add(array('shop_receipt_index', $block), array(
                'url' => acmsLink(array('admin' => 'shop_receipt_index', 'bid' => BID))
            ));
        } elseif ( config('shop') == 'off' ) {
            $block  = 'disable';
        }

        $Tpl->add($block);

        return $Tpl->get();
    }
}
