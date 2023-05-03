<?php

class ACMS_POST_Shop_Form_Confirm extends ACMS_POST_Shop
{   
    function post()
    {
        $this->initVars();

        $Order = $this->extract('order');
        $Order->setMethod('payment', 'required', true);
        $Order->setMethod('deliver', 'required', true);
        $Order->validate(new ACMS_Validator());

        if ( $this->alreadySubmit() ) {
            $this->screenTrans();
        }

        if ( $this->Post->isValidAll() ) {

            $SESSION =& $this->openSession();
            $SESSION->set('payment', $Order->get('payment'));
            $SESSION->set('deliver', $Order->get('deliver'));
            $SESSION->set('request_date', $Order->get('request_date'));
            $SESSION->set('request_time', $Order->get('request_time'));
            $SESSION->set('request_others', $Order->get('request_others'));

            /*
            * 合計類の一括計算（税抜き計など）
            */
            $ADDRESS = $SESSION->getChild('address');
            $TEMP = $this->openCart();
            $amount = array();

            foreach ( $TEMP as $row ) {
                $price  = $row[$this->item_price];
                $qty    = $row[$this->item_qty];
                $tax    = $row[$this->item_price.'#tax'];
                $sum    = $row[$this->item_price.'#sum'];

                @$amount['amount']   += intval($qty);
                @$amount['subtotal'] += intval($sum);
                @$amount['tax-only'] += intval($tax   * $qty);
                @$amount['tax-omit'] += intval($price * $qty);

                //if ( !empty($row[$this->item_except]) ) $except_flg = true;
                if ( !empty($row[$this->item_except]) && empty($except_flg) ){
                    $except_flg = ($row[$this->item_except] == 'on') ? true: false;
                }
            }

            /*
            * detect exception item
            */
            if ( !empty($except_flg) ) {
                $SESSION->set($this->item_except, 'on');
            } elseif ( !$SESSION->isNull($this->item_except) ) {
                $SESSION->delete($this->item_except);
            }

            /*
            * 諸費用の計算（送料／支払い手数料）
            */
            $charge = array();
            $charge['deliver'] = $this->deliverCharge($SESSION->get('deliver'), $ADDRESS->get('prefecture'));
            $charge['payment'] = $this->paymentCharge($SESSION->get('payment'));
            $charge['others']  = $this->othersCharge($SESSION->get('request_others'));

            $common = config('shop_order_shipping_common');
            $charge['deliver'] = is_numeric($common) ? $common : $charge['deliver'];
            $charge['deliver'] = ( $amount['subtotal'] < config('shop_order_shipping_limit') ) ? $charge['deliver'] : 0;

            // total = subtotal + charge( deliver + payment + others )
            $involve = explode('-', config('shop_total_involve'));
            $amount['total'] = $amount['subtotal'];
            foreach ( $involve as $row ) {
                if ( $row != ('deliver'||'payment'||'others') ) continue;
                $amount['total'] += $charge[$row];
            }

            $amount['charge#deliver'] = $charge['deliver'];
            $amount['charge#payment'] = $charge['payment'];
            $amount['charge#others']  = $charge['others'];
            $amount['settle']  = $this->settleMethod($SESSION->get('payment'));

            foreach ( $amount as $key => $val ) {
                $SESSION->set($key, $val);
            }

            $this->closeSession($SESSION);

            $step = 'confirm';

        } else {

            $step = 'deliver';

        }

        $this->Post->set('step', $step);
        return $this->Post;
    }

    function othersCharge($request)
    {
        $labels = configArray('shop_order_request_others_label');
        $charge = configArray('shop_order_request_others_charge');

        foreach ( $labels as $key => $label ) {
            if ( $request == $label ) {
                return @$charge[$key];
            }
        }
    }

    function deliverCharge($deliver, $prefecture = null)
    {
        $labels = configArray('shop_order_deliver_label');
        $charge = configArray('shop_order_deliver_charge');
        
        foreach ( $labels as $key => $label ) {
            if ( $deliver == $label ) {
                if ( !is_numeric(@$charge[$key]) && !empty($prefecture) ) {
                    return $this->shipping($prefecture);
                } else {
                    return @$charge[$key];
                }
            }
        }
    }

    function paymentCharge($payment)
    {
        $labels = configArray('shop_order_payment_label');
        $charge = configArray('shop_order_payment_charge');

        foreach ( $labels as $key => $label ) {
            if ( $payment == $label ) {
                return intval(@$charge[$key]);
            }
        }
    }

    function settleMethod($payment)
    {
        $labels   = configArray('shop_order_payment_label');
        $kuroneko = config('shop_order_payment_kuroneko');
        $sgp      = config('shop_order_payment_sgp');
        $paypal   = config('shop_order_payment_paypal');

        if ( @$labels[$kuroneko] == $payment ) {
            return 'kuroneko';
        }
        if ( @$labels[$sgp] == $payment ) {
            return 'sgp';
        }
        if ( @$labels[$paypal] == $payment ) {
            return 'paypal';
        }

    }

    function shipping($prefecture)
    {
        $labels = configArray('shop_order_shipping_label');
        $charge = configArray('shop_order_shipping_charge');

        foreach ( $labels as $key => $label ) {
            if ( $prefecture == $label ) {
                return @$charge[$key];
            }
        }
    }
}