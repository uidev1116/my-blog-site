<?php

class ACMS_POST_Shop_Address_Update extends ACMS_POST_Shop
{
    function post()
    {
        $Address = $this->extract('address');
        $Address->setMethod('name', 'required');
        $Address->setMethod('zip', 'required');
        $Address->setMethod('prefecture', 'required');
        $Address->setMethod('city', 'required');
        $Address->setMethod('field_1', 'required');
        $Address->setMethod('telephone', 'required');

        $Address->validate(new ACMS_Validator());
        
        $deleteField = new Field();
        $Field = $this->extract('field', new ACMS_Validator(), $deleteField);
		
        if ( $this->Post->isValidAll() ) {
            $DB     = DB::singleton(dsn());
            //------
            // update
            $SQL    = SQL::newUpdate('shop_address');
            $SQL->addUpdate('address_name', $Address->get('name'));
            $SQL->addUpdate('address_ruby', $Address->get('ruby'));
            $SQL->addUpdate('address_country', $Address->get('country'));
            $SQL->addUpdate('address_zip', $Address->get('zip'));
            $SQL->addUpdate('address_prefecture', $Address->get('prefecture'));
            $SQL->addUpdate('address_city', $Address->get('city'));
            $SQL->addUpdate('address_field_1', $Address->get('field_1'));
            $SQL->addUpdate('address_field_2', $Address->get('field_2'));
            $SQL->addUpdate('address_telephone', $Address->get('telephone'));
            
            $SQL->addWhereOpr('address_id', $Address->get('id'));
            $SQL->addWhereOpr('address_user_id', SUID);
            
			Common::saveField('uid', SUID, $Field, $deleteField);
			
            $DB->query($SQL->get(dsn()), 'exec');
            $this->Post->set('step', 'result');
        } else {
            $this->Post->set('step', 'reapply');
        }

        return $this->Post;
    }
}
