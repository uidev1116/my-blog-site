<?php

class ACMS_POST_Shop2_Form_Checkout extends ACMS_POST_Shop2
{
    function post()
    {
        $this->initVars();

        if ( !$this->inventoryCheck() ) {
            return $this->Post;
        }

        if ( $this->alreadySubmit() ) {
            $this->screenTrans();
        }

        if ( !!ACMS_SID || $this->config->get('shop_order_login') != 'required' ) {
            $this->Post->set('step', 'address');
            return $this->Post;
        } else {
            $this->setReferer($this->orderTpl, 'address');
            $this->screenTrans($this->loginTpl);
        }
    }

    function inventoryCheck()
    {
        $TEMP = $this->openCart(BID);

        foreach ( $TEMP as $hash => $row ) {
            if ( 0
                or !isset($row[$this->item_price])
                or !isset($row[$this->item_qty])
            ) {
                continue;
            }

            $efield = loadEntryField(intval($row[$this->item_id]));
            $item_stock = $efield->get($this->item_sku);
            if( isset($item_stock) && ($item_stock > 0) ){
                if( intval($item_stock) < intval($row[$this->item_qty]) ){
                    return false;
                }
            }
        }
        return true;
    }
}
