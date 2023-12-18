<?php

class ACMS_GET_Shop2_Form_Order extends ACMS_GET_Shop2
{
    function get()
    {
        $this->initVars();

        $SESSION =& $this->openSession();
        $TEMP   = $this->openCart();

        $Order  =& $this->Post->getChild('order');
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        $step   = $this->Get->get('step');
        if ( $this->Post->isValidAll() ) $step  = $this->Post->get('step', $step);

        $root  = !(empty($step) or is_bool($step)) ? 'step#'.$step : 'step';
        $vars   = $this->buildField($Order, $Tpl, $root, 'order');
        $vars['step'] = $step;

        if ( $root == 'step' ) {
            $SESSION->delete('submitted');
            $SESSION->delete('portrait_cart');
        }

        if ( $step == 'confirm' ) {
            // Cart

            $TEMP = $this->openCart();
            foreach ( $TEMP as $item ) {

                /*if ( config('shop_tax_calculate') != 'extax' ) {
                    $item[$this->item_price] += $item[$this->item_price.'#tax'];
                }*/
                $Tpl->add(array('item:loop', 'cart', $root), $item);
            }

            // Address
            $Primary = $SESSION->getChild('primary');
            $Address = $SESSION->getChild('address');

            $Tpl->add(array('primary', $root), $this->buildField($Primary, $Tpl, array('primary', $root)));
            $Tpl->add(array('address', $root), $this->buildField($Address, $Tpl, array('address', $root)));

            // Session
            $vars += $this->buildField($SESSION, $Tpl, $root);
        }

        if ( $step == 'result' ) {
            $_vars   = null;
            $this->initVars();
            $_vars   = array('code' => $this->Post->get('code'));

            $Field      = $this->Post->getChild('field');
            $Primary    = $SESSION->getChild('primary');
            $TEMP       = $SESSION->getArray('portrait_cart');
            $block      = $Field->get('settle');

            $_vars += $this->buildField($Field, $Tpl, array($block, $root));
            $Tpl->add(array($block, $root), $_vars);
        }

        $Tpl->add($root, $vars);
        return $Tpl->get();
    }
}