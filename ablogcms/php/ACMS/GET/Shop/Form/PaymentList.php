<?php

class ACMS_GET_Shop_Form_PaymentList extends ACMS_GET_Shop
{
    function get()
    {
        $this->initVars();
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        if ( !$this->detectEdition() ) return $this->LICENSE_ERR_MSG;
        $SESSION =& $this->openSession();

        $payments = configArray('shop_order_payment_label');
        $charge   = configArray('shop_order_payment_charge');
        
        foreach ( $payments as $key => $payment ) {

            $vars = array('payment' => $payment,
                          'charge'  => @$charge[$key],
                          );
  
            if ( $SESSION->get('payment') == $payment ) {
                $vars += array('selected' => config('attr_selected'),
                               'checked'  => config('attr_checked'),
                               );
            }

            $Tpl->add('payment:loop', $vars);
        }
        
        return $Tpl->get();
    }
}