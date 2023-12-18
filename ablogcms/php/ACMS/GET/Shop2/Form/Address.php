<?php

class ACMS_GET_Shop2_Form_Address extends ACMS_GET_Shop2
{
    function get()
    {
        $this->initVars();
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        $SESSION =& $this->openSession();

        $Order     =& $this->Post->getChild('order');
        $Primary   =& $this->Post->getChild('primary');
        $Secondary =& $this->Post->getChild('secondary');
        if ( $Primary->isNull() ) $Primary->overload($SESSION->getChild('primary'));
        if ( $Secondary->isNull() && $SESSION->get('sendto') == 'secondary' ) {
            $Secondary->overload($SESSION->getChild('address'));
            $fds = $Secondary->listFields();
            foreach ( $fds as $fd ) {
                $Secondary->set($fd.'#2', $Secondary->get($fd));
                $Secondary->delete($fd);
            }
        }

        if ( !is_null(SUID) ) {
            $DB     = DB::singleton(dsn());
            $SQL    = SQL::newSelect('shop_address');
            $SQL->addWhereOpr('address_user_id', SUID);
            $all = $DB->query($SQL->get(dsn()), 'all');
    
            $Registed  =& $this->loadRegisted();
            if ( $Primary->isNull() ) $Primary->overload($this->loadPrimary());
        }

        /*
        * primary
        */
        if ( is_null(SUID) ) {
            if ( $SESSION->isExists('mail') ) {
                $Tpl->add(array('mailTo:veil', 'address#primary'), array('mail' => $SESSION->get('mail')));
                $Primary->set('mail', '');
            } else {
                $Tpl->add(array('mailTo:veil', 'address#primary'), array('mail' => $Primary->get('mail')));
            }
            //$Primary->delete('mail');
        } else {
            /*
            * if session have invalid 'mail' strings.
            */
            if ( !preg_match(REGEX_VALID_MAIL, $SESSION->get('mail')) ) {
                $SQL = SQL::newSelect('user');
                $SQL->addSelect('user_mail');
                $SQL->addWhereOpr('user_id', SUID);
                $mail = $DB->query($SQL->get(dsn()), 'one');
                $SESSION->set('mail', $mail);
            }
            $Tpl->add(array('registTo:veil', 'address#primary'), array('mail' => $SESSION->get('mail')));
        }
        $Tpl->add('address#primary', $this->buildField($Primary, $Tpl, 'address#primary'));

        /*
        * secondary
        */
        $Tpl->add('address#secondary', $this->buildField($Secondary, $Tpl, 'address#secondary'));

        /*
        * registed
        */
        if ( !empty($Registed) ) {
            foreach ( $Registed as $row ) {
                $vars  = $this->buildField($row, $Tpl, array('address:loop', 'address#registed'));
                $Tpl->add(array('address:loop', 'address#registed'), $vars);
            }
            $Tpl->add('address#registed');
        }

        $vars = $this->buildField($Order, $Tpl);
        if ( $Order->isNull() ) $vars += array(
                        'sendto:checked#'.$SESSION->get('sendto') => config('attr_checked'),
                        'sendto:selected#'.$SESSION->get('sendto') => config('attr_selected'),
                        );
        $Tpl->add(null, $vars);

        return $Tpl->get();
    }

    function & loadRegisted()
    {
        $Registed = array();
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('shop_address');
        $SQL->addWhereOpr('address_user_id', SUID);
        $SQL->addWhereOpr('address_primary', 'off');
        if ( $all = $DB->query($SQL->get(dsn()), 'all') ) {
            foreach ( $all as $row ) {
                $Address   = new Field_Validation();
                foreach ( $row as $key => $val ) {
                    $Address->set(substr($key, strlen('address_')), $val);
                }
                $Registed[] = $Address;
            }
        }
        return $Registed;
    }

    function loadPrimary()
    {
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('shop_address');
        $SQL->addWhereOpr('address_user_id', SUID);
        $SQL->addWhereOpr('address_primary', 'on');
        if ( $row = $DB->query($SQL->get(dsn()), 'row') ) {
            $Primary   = new Field_Validation();
            foreach ( $row as $key => $val ) {
                $Primary->set(substr($key, strlen('address_')), $val);
            }
            return $Primary;
        }
    }
}