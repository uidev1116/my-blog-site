<?php

// Cart_Add & Cart_Delete の動作に限り任意のBIDのカートへのアクセスを許可する

class ACMS_POST_ShopLite extends ACMS_POST
{
    function openCart($bid = null)
    {
        $this->initBid($bid);

        if ( !empty($_SESSION[$this->cname.$bid]) ) {
            return @$_SESSION[$this->cname.$bid];
        } else {
            return array();
        }
    }

    function closeCart($CART, $bid)
    {
        $this->initBid($bid);

        $_SESSION[$this->cname.$bid] = $CART;
    }

    function initBid(& $bid)
    {
        $bid = ( is_null($bid) || !is_int($bid) ) ? BID : $bid;
    }

}
class ACMS_POST_Shop extends ACMS_POST_ShopLite
{
    function initVars()
    {
        $this->item_id      = config('shop_item_id');
        $this->item_name    = config('shop_item_name');
        $this->item_price   = config('shop_item_price');
        $this->item_qty     = config('shop_item_qty');
        $this->item_sku     = config('shop_item_sku');
        $this->item_category= config('shop_item_category');
        $this->item_except  = config('shop_item_exception');

        $this->sname        = config('shop_session');
        $this->cname        = config('shop_cart');

        $this->addedTpl     = config('shop_tpl_added');
        $this->orderTpl     = config('shop_tpl_order');
        $this->loginTpl     = config('shop_tpl_login');

        $this->cartTpl      = config('shop_tpl_cart'); // kari

        session_name($this->sname);
        @session_start();
        acmsSetCookie('acms_config_cache', 'off');
    }

    function sanitize(&$data)
    {
        if ( is_array($data) ) {
            return array_map(array(&$this, 'sanitize'), $data);
        } else {
            $data = htmlentities($data, ENT_QUOTES, 'UTF-8');
            return $data;
        }
    }

    function alreadySubmit()
    {
        $TEMP       = $this->openCart();
        $SESSION    = $this->openSession();
        if ( $SESSION->isExists('portrait_cart') && empty($TEMP) ) {
            return true;
        } else {
            return false;
        }
    }

    function openSession()
    {
        if ( !$this->detectEdition() ) die;

        if ( !ACMS_SID && !empty($_SESSION[$this->sname.BID]) ) {
            return $_SESSION[$this->sname.BID];
        } elseif ( !ACMS_SID && empty($_SESSION[$this->sname.BID]) ) {
            return new Field;
        } elseif ( !!ACMS_SID && !empty($_SESSION[$this->sname.BID]) ) {
            $SESSION = Field::singleton('session');
            $SESSION = $_SESSION[$this->sname.BID];
            unset($_SESSION[$this->sname.BID]);
            return $SESSION;
        } elseif ( !!ACMS_SID && empty($_SESSION[$this->sname.BID]) ) {
            return Field::singleton('session');
        }
    }

    function closeSession($DATA)
    {
        if ( !ACMS_SID ) {
            $_SESSION[$this->sname.BID] = $DATA;
        } elseif ( !!ACMS_SID ) {
            // script shutdown, when session is auto saving.
        }
    }

    function openCart($bid = null)
    {
        $this->initBid($bid);

        if ( !$this->detectEdition() ) return parent::openCart();

        if ( !!ACMS_SID ) {
            $temp = $this->loadCart(ACMS_SID, $bid);
            return !empty($temp) ? $temp : @$_SESSION[$this->cname.$bid];
        } elseif ( !ACMS_SID && !empty($_SESSION[$this->cname.$bid]) ) {
            return @$_SESSION[$this->cname.$bid];
        } else {
            return array();
        }
    }

    function closeCart($CART, $bid = null)
    {
        $this->initBid($bid);

        if ( !$this->detectEdition() ) return parent::closeCart($CART, $bid);

        $_SESSION[$this->cname.$bid] = $CART;

        if ( !!ACMS_SID ) {
            $CART   = serialize($CART);
            $DB     = DB::singleton(dsn());

            $SQL    = SQL::newDelete('shop_cart');
            $SQL->addWhereOpr('cart_session_id', ACMS_SID);
            $SQL->addWhereOpr('cart_blog_id', $bid);
            $DB->query($SQL->get(dsn()), 'exec');

            $SQL    = SQL::newInsert('shop_cart');
            $SQL->addInsert('cart_data', $CART);
            $SQL->addInsert('cart_session_id', ACMS_SID);
            $SQL->addInsert('cart_blog_id', $bid);
            $res = $DB->query($SQL->get(dsn()), 'exec');
        }
    }

    function loadCart($sid, $bid)
    {
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('shop_cart');
        $SQL->addSelect('cart_data');
        $SQL->addWhereOpr('cart_session_id', $sid);
        $SQL->addWhereOpr('cart_blog_id', $bid);
        $DATA   = $DB->query($SQL->get(dsn()), 'row');
        return @unserialize($DATA['cart_data']);
    }

    function setReferer($page, $step = null)
    {
        $SESSION =& $this->openSession();
        $SESSION->set('storePage', $page);
        $SESSION->set('storeStep', $step);
        $this->closeSession($SESSION);
    }

    function screenTrans($page = null, $step = null, $bid = BID)
    {
        if ( !empty($step) ) {
            $this->redirect(acmsLink(array('tpl' => $page, 'query' => array('step' => $step))));
        } else {
            if ( empty($page) ) {
                $this->redirect(acmsLink());
            } else {
                $this->redirect(acmsLink(array('bid'=> $bid, 'cid'=> null, 'eid'=>null, 'tpl'=> $page)));
            }
        }
    }

    function detectEdition()
    {
        if ( defined('LICENSE_PLUGIN_SHOP_PRO') ) {
            return constant('LICENSE_PLUGIN_SHOP_PRO');
        } else {
            return false;
        }
    }
}
