<?php

class ACMS_POST_Shop_Customer_Update extends ACMS_POST_Shop
{   
    function post()
    {
        if ( !sessionWithAdministration() ) return die();

        $DB = DB::singleton(dsn());

        $User = $this->extract('user');
        $Address = $this->extract('address');

        $User->setMethod('name', 'required', true);
        $User->setMethod('mail', 'required', true);
        $User->validate(new ACMS_Validator());

        $Address->setMethod('address_name', 'required', true);
        $Address->setMethod('address_ruby', 'required', true);
        $Address->setMethod('address_zip', 'required', true);
        $Address->setMethod('address_prefecture', 'required', true);
        $Address->setMethod('address_city', 'required', true);
        $Address->setMethod('address_field_1', 'required', true);
        $Address->setMethod('address_telephone', 'required', true);
        $Address->validate(new ACMS_Validator());

        $Field = $this->extract('address', new ACMS_Validator());

        if ( $this->Post->isValidAll() ) {
            /*
            * user
            */
            $SQL = SQL::newUpdate('user');
            $SQL->addUpdate('user_name', $User->get('name'));
            $SQL->addUpdate('user_mail', $User->get('mail'));
            $SQL->addWhereOpr('user_id', UID);
            $DB->query($SQL->get(dsn()), 'exec');

            /*
            * primary address
            */
            $SQL = SQL::newUpdate('shop_address');
            $SQL->addUpdate('address_name', $Address->get('address_name'));
            $SQL->addUpdate('address_ruby', $Address->get('address_ruby'));
            $SQL->addUpdate('address_zip', $Address->get('address_zip'));
            $SQL->addUpdate('address_prefecture', $Address->get('address_prefecture'));
            $SQL->addUpdate('address_city', $Address->get('address_city'));
            $SQL->addUpdate('address_field_1', $Address->get('address_field_1'));
            $SQL->addUpdate('address_telephone', $Address->get('address_telephone'));
            $SQL->addWhereOpr('address_user_id', UID);
            $SQL->addWhereOpr('address_primary', 'on');

            /*
            * user field
            */
            Common::saveField('uid', UID, $Field);

            ACMS_RAM::user(UID, null);
        }


        return $this->Post;
    }
}
