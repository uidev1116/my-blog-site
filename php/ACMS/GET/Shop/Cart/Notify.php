<?php

class ACMS_GET_Shop_Cart_Notify extends ACMS_GET_Shop
{
    function get()
    {	
        $this->initVars();

        $step   = $this->Post->get('step', 'apply');
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        if ( !empty($_SESSION['added']) ) {
            if ( config('shop_tax_calculate') != 'intax' ) {
                $_SESSION['added'][$this->item_price] -= $_SESSION['added'][$this->item_price.'#tax'];
            }
            $Tpl->add('added', $this->sanitize($_SESSION['added']));
            unset($_SESSION['added']);
        } elseif ( !empty($_SESSION['deleted']) ) {
            $Tpl->add('deleted', $this->sanitize($_SESSION['deleted']));
            unset($_SESSION['deleted']);
        } else {
            return '';
        }

        return $Tpl->get();
    }
}