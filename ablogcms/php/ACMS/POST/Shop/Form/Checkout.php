<?php

class ACMS_POST_Shop_Form_Checkout extends ACMS_POST_Shop
{
    function post()
    {
        $this->initVars();

        if ( $this->alreadySubmit() ) {
            $this->screenTrans();
        }

        if ( !!ACMS_SID || config('shop_order_login') != 'required' ) {
            $this->Post->set('step', 'address');
            return $this->Post;
        } else {
            $this->setReferer($this->orderTpl, 'address');
            $this->screenTrans($this->loginTpl);
        }
    }
}