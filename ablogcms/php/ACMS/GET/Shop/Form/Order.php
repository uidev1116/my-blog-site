<?php

class ACMS_GET_Shop_Form_Order extends ACMS_GET_Shop
{
    function get()
    {
        $this->initVars();
        if ( !$this->detectEdition() ) return $this->LICENSE_ERR_MSG;

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
                if ( config('shop_tax_calculate') != 'extax' ) {
                    $item[$this->item_price] += $item[$this->item_price.'#tax'];
                }
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

            if ( $Field->get('settle') == 'kuroneko' ) {

                // kuroneko payment
                $_vars += $this->buildField($Field, $Tpl, array('kuronekoWebCollect', $root));
                $_vars += array(
                            'order_no'          => $SESSION->get('code'),
                            'goods_name'        => $TEMP[0][$this->item_name],
                            'settle_price'      => ( $SESSION->get('total') - $SESSION->get('charge#payment') ),
                            'buyer_name_kanji'  => preg_replace('/( |　)/', '', $Primary->get('name')),
                            'buyer_name_kana'   => preg_replace('/( |　)/', '', @mb_convert_kana($Primary->get('ruby'), 'CKV')),
                            'buyer_email'       => $SESSION->get('mail'),
                            'buyer_tel'         => $Primary->get('telephone'),
                            'trs_map'           => config('shop_order_payment_kuroneko_trs'),
                            'trader_code'       => config('shop_order_payment_kuroneko_code'),
                            'action_url'        => config('shop_order_payment_kuroneko_action'),
                            );
                $Tpl->add(array('kuronekoWebCollect', $root), $_vars);

            } elseif ( $Field->get('settle') == 'sgp' ) {

                // sg payment
                $_vars += $this->buildField($Field, $Tpl, array('sgPayment', $root));
                $_vars += array(
                            '_SGPid'         => config('shop_order_payment_sgp_id'),
                            '_mail'          => $SESSION->get('mail'),
                            '_price'         => ( $SESSION->get('total') - $SESSION->get('charge#payment') ),
                            '_opt1'          => $SESSION->get('code'),
                            );
                $Tpl->add(array('sgPayment', $root), $_vars);

            } elseif ( $Field->get('settle') == 'paypal' ) {

                // paypal
                $_vars += $this->buildField($Field, $Tpl, array('paypal', $root));
                $_vars += array(
                            'business'         => config('shop_order_payment_paypal_business'),
                            'amount_1'         => ( $SESSION->get('total') - $SESSION->get('charge#payment') ),
                            'item_name_1'      => $SESSION->get('code'),
                            );
                $Tpl->add(array('paypal', $root), $_vars);

            } else {

                $_vars += $this->buildField($Field, $Tpl, array('default', $root));
                $Tpl->add(array('default', $root), $_vars);

            }
        }

        $Tpl->add($root, $vars);
        return $Tpl->get();
    }
}