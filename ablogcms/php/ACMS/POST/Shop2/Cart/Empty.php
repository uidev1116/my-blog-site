<?php

class ACMS_POST_Shop2_Cart_Empty extends ACMS_POST_Shop2
{
    function post()
    {
        $this->initVars();

        $Cart = $this->extract('cart');
        $Cart->validate(new ACMS_Validator());

        $bid = $Cart->isNull('cart_bid') ? BID : intval($Cart->get('cart_bid', BID));
        $this->openCart($bid);

        $cart = $this->session->get($this->cname . $bid);
        if (!empty($cart)) {
            $this->session->delete($this->cname . $bid);
            $this->session->save();
        }

        if (!!ACMS_SID) {
            $DB     = DB::singleton(dsn());
            $SQL    = SQL::newDelete('shop_cart');
            $SQL->addWhereOpr('cart_session_id', ACMS_SID);
            $SQL->addWhereOpr('cart_blog_id', $bid);
            $DB->query($SQL->get(dsn()), 'exec');
        }

        return $this->Post;
    }
}
