<?php

class ACMS_GET_Shop_Address_List extends ACMS_GET_Shop
{
    function get()
    {
        if ( !sessionWithSubscription() ) return '';

        $Tpl        = new Template($this->tpl, new ACMS_Corrector());

        $this->addressList($Tpl);

        $this->addressPrimary($Tpl);

        return $Tpl->get();
    }

    function addressPrimary(& $Tpl)
    {
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('shop_address');
        $SQL->addWhereOpr('address_primary', 'on');
        $SQL->addWhereOpr('address_user_id', SUID);
        if ( $row = $DB->query($SQL->get(dsn()), 'row') ) {
            $Line = new Field();
            foreach ( $row as $key => $val ) {
                $Line->set(substr($key, strlen('address_')), $val);
            }
            $vars = $this->buildField($Line, $Tpl, 'address:primary');

            $Tpl->add('address:primary', $vars);
        } else {
            $Tpl->add('notYet');
        }
    }

    function addressList(& $Tpl)
    {
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('shop_address');
        if ( !$this->detectEdition() ) return $this->LICENSE_ERR_MSG;

        if ( !!ACMS_SID && !ADMIN ) {
            $SQL->addWhereOpr('address_primary', 'off');
            $SQL->addWhereOpr('address_user_id', SUID);
        } else {
            //return '';
        }

        $Pager  = new SQL_Select($SQL);
        $Pager->setSelect('*', 'entry_amount', null, 'count');
        if ( !$pageAmount = intval($DB->query($Pager->get(dsn()), 'one')) ) {
            $Tpl->add('notFound');
        } else {
            if ( $all = $DB->query($SQL->get(dsn()), 'all') ) {
                foreach ( $all as $row ) {
                    $Line = new Field();
                    foreach ( $row as $key => $val ) {
                        $Line->set(substr($key, strlen('address_')), $val);
                    }
                    $vars = $this->buildField($Line, $Tpl, 'address:loop');
    
                    $Tpl->add('address:loop', $vars);
                }
            }
        }
    }
}