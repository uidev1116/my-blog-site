<?php

// openCart & closeCart はカートの表示でありモジュールIDとルールの対象であるため、$this->bid
// openSession & closeSession はオーダーフォームのセンション管理下にあるため、BID


class ACMS_GET_Shop2 extends ACMS_GET
{
    protected $session;

    protected $config;

    function initVars()
    {
        $this->config = Config::loadDefaultField();
        $this->config->overload(Config::loadBlogConfig(BID));

        $this->item_id      = $this->config->get('shop_item_id');
        $this->item_name    = $this->config->get('shop_item_name');
        $this->item_price   = $this->config->get('shop_item_price');
        $this->item_qty     = $this->config->get('shop_item_qty');
        $this->item_sku     = $this->config->get('shop_item_sku');
        $this->item_category= $this->config->get('shop_item_category');
        $this->item_except  = $this->config->get('shop_item_exception');
        $this->cname        = $this->config->get('shop_cart');
        $this->sname        = $this->config->get('shop_session');
        $this->addedTpl     = $this->config->get('shop_tpl_added');
        $this->orderTpl     = $this->config->get('shop_tpl_order');
        $this->loginTpl     = $this->config->get('shop_tpl_login');
        $this->session = ACMS_Session::singleton();

        if ( !defined('NO_CACHE_PAGE') ) {
            define('NO_CACHE_PAGE', 1);
        }
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

    function & openSession()
    {
        $sname = $this->sname.BID;
        $session = $this->session->get($sname);

        if ( !ACMS_SID && !empty($session) ) {
            return $session;
        } else if ( !ACMS_SID && empty($session) ) {
            $field = new Field;
            return $field;
        } else if ( !!ACMS_SID && !empty($session) ) {
            $this->session->delete($sname);
            $this->session->save();
            return $session;
        } else if ( !!ACMS_SID && empty($session) ) {
            return Field::singleton('session');
        }
    }

    function closeSession($DATA)
    {
        if ( !ACMS_SID ) {
            $this->session->set($this->sname.BID, $DATA);
            $this->session->save();
        } elseif ( !!ACMS_SID ) {
            // script shutdown, when session is auto saving.
        }
    }

    function openCart()
    {
        $cart = $this->session->get($this->cname.$this->bid);
        if ( !!ACMS_SID ) {
            $temp = $this->loadCart(ACMS_SID);
            return !empty($temp) ? $temp : $cart;
        } elseif ( !ACMS_SID && !empty($cart) ) {
            return $cart;
        } else {
            return array();
        }
    }

    function closeCart($CART)
    {
        $this->session->set($this->cname.$this->bid, $CART);
        $this->session->save();

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
}
