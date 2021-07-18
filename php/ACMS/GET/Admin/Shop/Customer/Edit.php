<?php

class ACMS_GET_Admin_Shop_Customer_Edit extends ACMS_GET_Admin_Edit
{
    function edit(& $Tpl)
    {
        if ( !UID ) return '';

        $User       =& $this->Post->getChild('user');
        $Field      =& $this->Post->getChild('field');
        $Address    =& $this->Post->getChild('address');

        if ( $User->isNull() ) {
            if ( !!UID ) {
                $User->overload(loadUser(UID));
                $Field->overload(loadUserField(UID));
                $Address->overload($this->loadPrimary(UID));
            } else {
                /*
                管理側の新規インサートはない
                $Address->set('key', 'val');
                */
            }
        }
        
        return true;
    }

    function loadPrimary($uid)
    {
        $Address   = new Field_Validation();
        if ( !empty($uid) ) {
            $DB     = DB::singleton(dsn());
            $SQL    = SQL::newSelect('shop_address');
            $SQL->addWhereOpr('address_user_id', $uid);
            $SQL->addWhereOpr('address_primary', 'on');
            if ( $row = $DB->query($SQL->get(dsn()), 'row') ) {
                foreach ( $row as $key => $val ) {
                    $Address->set($key, $val);
                }
            }
        }
        return $Address;
    }
}