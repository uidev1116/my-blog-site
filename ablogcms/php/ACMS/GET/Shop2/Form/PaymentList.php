<?php

class ACMS_GET_Shop2_Form_PaymentList extends ACMS_GET_Shop2
{
    function get()
    {
        $this->initVars();
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        $SESSION =& $this->openSession();

        $payments = $this->config->getArray('shop_order_payment_label');
        $charge   = $this->config->getArray('shop_order_payment_charge');

        foreach ($payments as $key => $payment) {
            $vars = array('payment' => $payment,
                          'charge'  => @$charge[$key],
                          );

            if ($SESSION->get('payment') == $payment) {
                $vars += array('selected' => config('attr_selected'),
                               'checked'  => config('attr_checked'),
                               );
            }

            $Tpl->add('payment:loop', $vars);
        }

        return $Tpl->get();
    }
}
