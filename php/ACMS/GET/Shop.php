<?php

// openCart & closeCart はカートの表示でありモジュールIDとルールの対象であるため、$this->bid
// openSession & closeSession はオーダーフォームのセンション管理下にあるため、BID

class ACMS_GET_ShopLite extends ACMS_GET
{
    function openCart()
    {
        if ( !empty($_SESSION[$this->cname.$this->bid]) ) {
            return @$_SESSION[$this->cname.$this->bid];
        } else {
            return array();
        }
    }

    function closeCart($CART)
    {
        $_SESSION[$this->cname.$this->bid] = $CART;
    }
}

class ACMS_GET_Shop extends ACMS_GET_ShopLite
{
    var $LICENSE_ERR_MSG = 'ショップPRO版のモジュールをご利用いただく場合は、ライセンスをご購入ください。';

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

        session_name($this->sname);
        @session_start();
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

    function openSession()
    {
        if ( !$this->detectEdition() ) return false;

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

    function openCart()
    {
        if ( !$this->detectEdition() ) return parent::openCart();
        
        if ( !!ACMS_SID ) {
            $temp = $this->loadCart(ACMS_SID);
            return !empty($temp) ? $temp : @$_SESSION[$this->cname.$this->bid];
        } elseif ( !ACMS_SID && !empty($_SESSION[$this->cname.$this->bid]) ) {
            return @$_SESSION[$this->cname.$this->bid];
        } else {
            return array();
        }
    }

    function closeCart($CART)
    {
        if ( !$this->detectEdition() ) return parent::closeCart($CART);

        $_SESSION[$this->cname.$this->bid] = $CART;

        if ( !!ACMS_SID ) {
            $CART   = serialize($CART);
            $DB     = DB::singleton(dsn());
            
            $SQL    = SQL::newDelete('shop_cart');
            $SQL->addWhereOpr('cart_session_id', ACMS_SID);
            $SQL->addWhereOpr('cart_blog_id', $this->bid);
            $DB->query($SQL->get(dsn()), 'exec');
            
            $SQL    = SQL::newInsert('shop_cart');
            $SQL->addInsert('cart_data', $CART);
            $SQL->addInsert('cart_session_id', ACMS_SID);
            $SQL->addInsert('cart_blog_id', $this->bid);
            $res = $DB->query($SQL->get(dsn()), 'exec');
        }
    }

    function loadCart($sid)
    {
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('shop_cart');
        $SQL->addSelect('cart_data');
        $SQL->addWhereOpr('cart_session_id', $sid);
        $SQL->addWhereOpr('cart_blog_id', $this->bid);
        $DATA   = $DB->query($SQL->get(dsn()), 'row');
        return @unserialize($DATA['cart_data']);
    }

    function screenTrans($page = null, $step = null)
    {
        if ( !empty($step) ) {
            $this->redirect(acmsLink(array('tpl' => $page, 'query' => array('step' => $step))));
        } else {
            $this->redirect(acmsLink(array('tpl' => $page)));
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