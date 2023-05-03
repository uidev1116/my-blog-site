<?php

class ACMS_POST_Shop_Address_Insert extends ACMS_POST_Shop
{
    function post()
    {
        $Address = $this->extract('address');
        $Address->setMethod('name', 'required');
        $Address->setMethod('ruby', 'required');
        $Address->setMethod('zip', 'required');
        $Address->setMethod('prefecture', 'required');
        $Address->setMethod('city', 'required');
        $Address->setMethod('field_1', 'required');
        $Address->setMethod('telephone', 'required');
        $Address->validate(new ACMS_Validator());

		$Field = $this->extract('field', new ACMS_Validator());
		 
       if ( $this->Post->isValidAll() ) {

            $DB     = DB::singleton(dsn());

            /**
             * if user's address do not makes yet. when registing as primary address.
             */
            /*
            $SQL    = SQL::newSelect('shop_address');
            $SQL->addSelect('*', 'address_amount', null, 'COUNT');
            $SQL->addWhereOpr('address_user_id', SUID);
            $row    = $DB->query($SQL->get(dsn()), 'one');
            $primary = empty($row) ? 'on' : 'off';
            */

            //------
            // insert
            $aid    = $DB->query(SQL::nextval('shop_address_id', dsn()), 'seq');
            $SQL    = SQL::newInsert('shop_address');
            $SQL->addInsert('address_id', $aid);
            //$SQL->addInsert('address_primary', $primary);
            $SQL->addInsert('address_primary', 'off');
            $SQL->addInsert('address_name', $Address->get('name'));
            $SQL->addInsert('address_ruby', $Address->get('ruby'));
            $SQL->addInsert('address_country', $Address->get('country'));
            $SQL->addInsert('address_zip', $Address->get('zip'));
            $SQL->addInsert('address_prefecture', $Address->get('prefecture'));
            $SQL->addInsert('address_city', $Address->get('city'));
            $SQL->addInsert('address_field_1', $Address->get('field_1'));
            $SQL->addInsert('address_field_2', $Address->get('field_2'));
            $SQL->addInsert('address_telephone', $Address->get('telephone'));
            $SQL->addInsert('address_user_id', SUID);
            $SQL->addInsert('address_blog_id', BID);

            $DB->query($SQL->get(dsn()), 'exec');
			
			Common::saveField('uid', SUID, $Field);
			
            $this->Post->set('step', 'result');
        } else {
            $this->Post->set('step', 'reapply');
        }

        return $this->Post;
    }
}
