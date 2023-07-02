<?php

class ACMS_GET_Shop_Form_Tracking extends ACMS_GET_Shop
{
    function get()
    {
        $this->initVars();

        if ( !$this->detectEdition() ) return $this->LICENSE_ERR_MSG;
        $SESSION  =& $this->openSession();

        if ( $SESSION->isNull('submitted') ) {

            $ADDRESS  =  $SESSION->getChild('address');
            $TEMP     =  $SESSION->getArray('portrait_cart');

            $Tpl    = new Template(config('shop_order_tracking_code'), new ACMS_Corrector());

            foreach ( $TEMP as $item ) {

                $price  = !empty($item[$this->item_price])  ? $item[$this->item_price]  : 0;
                $qty    = !empty($item[$this->item_qty])    ? $item[$this->item_qty]    : 0;
                $name   = !empty($item[$this->item_name])   ? $item[$this->item_name]   : 'unknown';
                $sku    = !empty($item[$this->item_sku])    ? $item[$this->item_sku]    : 0;
                $cate   = !empty($item[$this->item_category]) ? $item[$this->item_category] : 'unknown';

                $vars = array(
                            'code'      => $SESSION->get('code'),
                            'price'     => $price,
                            'quantity'  => $qty,
                            'name'      => $name,
                            'stock'     => $sku,
                            'category'  => $cate,
                            );
                $Tpl->add('item:loop', $vars);
            }

            $vars = array(
                        'code'      => $SESSION->get('code'),
                        'payment'   => $SESSION->get('payment', 'unknown'),
                        'total'     => $SESSION->get('total', 0),
                        'tax'       => $SESSION->get('tax-only', 0),
                        'shipping'  => $SESSION->get('charge#deliver', 0),
                        'city'      => $ADDRESS->get('city', 'unknown'),
                        'prefecture'=> $ADDRESS->get('prefecture', 'unknown'),
                        'country'   => $ADDRESS->get('country', 'Japan'),
                        );

            $Tpl->add(null, $vars);

            return $Tpl->get();
        } else {
            return '';
        }
    }
}