<?php

class ACMS_GET_Shop2_Cart_Empty extends ACMS_GET_Shop2
{
    function get()
    {
        $this->initVars();

        $step   = $this->Post->get('step', 'apply');
        $bid    = BID;

        if ( $step == 'result' ) {
            $cart = $this->session->get($this->cname.$bid);
            if ( !empty($cart) ) {
                $this->session->delete($this->cname.$bid);
                $this->session->save();
            }
            if ( !!ACMS_SID ) {
                $DB     = DB::singleton(dsn());
                $SQL    = SQL::newDelete('shop_cart');
                $SQL->addWhereOpr('cart_session_id', ACMS_SID);
                $SQL->addWhereOpr('cart_blog_id', $bid);
                $DB->query($SQL->get(dsn()), 'exec');
            }
        }
        return '';
    }
}
