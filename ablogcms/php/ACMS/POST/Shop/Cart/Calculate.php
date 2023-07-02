<?php

class ACMS_POST_Shop_Cart_Calculate extends ACMS_POST_Shop
{
    function post()
    {
        $this->initVars();

        $Cart = $this->extract('cart');
        $Cart->validate(new ACMS_Validator());
        $bid    = $Cart->isNull('cart_bid') ? BID : intval($Cart->get('cart_bid', BID));

        if ( $Cart->isValid() ) {
            $TEMP = $this->openCart($bid);
    
            $fds = $Cart->listFields();
            foreach ( $fds as $fd ) {
                $qty = intval($Cart->get($fd));
                $TEMP[$fd][$this->item_qty] = $qty;
                if ( empty($TEMP[$fd][$this->item_qty]) ) {
                    unset($TEMP[$fd]);
                } else {
                    $price  = $TEMP[$fd][$this->item_price];
                    $tax    = $TEMP[$fd][$this->item_price.'#tax'];
                    $TEMP[$fd][$this->item_price.'#sum'] = ( $price + $tax ) * $qty;
                }
            }
    
            $this->closeCart($TEMP, $bid);
        }
        $this->Post->set('step', null);
        return $this->Post;
    }
}