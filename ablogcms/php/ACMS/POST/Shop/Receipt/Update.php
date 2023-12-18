<?php

class ACMS_POST_Shop_Receipt_Update extends ACMS_POST_Shop
{   
    function post()
    {
        if ( !sessionWithAdministration() ) return die();

        $DB = DB::singleton(dsn());

        $Receipt    = $this->extract('receipt');
        $Address    = $this->extract('address');
        $Receipt->set('address', serialize($Address));

        $Receipt->setMethod('payment', 'required', true);
        $Receipt->setMethod('deliver', 'required', true);
        $Receipt->setMethod('address', 'required', true);
        $Receipt->setMethod('total', 'required', true);
        $Receipt->setMethod('subtotal', 'required', true);
        $Receipt->setMethod('total', 'digits', true);
        $Receipt->setMethod('subtotal', 'digits', true);
        $Receipt->validate(new ACMS_Validator());

        if ( $this->Post->isValidAll() ) {
            $SQL = SQL::newUpdate('shop_receipt');
            $SQL->addUpdate('receipt_payment', $Receipt->get('payment'));
            $SQL->addUpdate('receipt_deliver', $Receipt->get('deliver'));
            $SQL->addUpdate('receipt_address', $Receipt->get('address'));
            $SQL->addUpdate('receipt_total', $Receipt->get('total'));
            $SQL->addUpdate('receipt_subtotal', $Receipt->get('subtotal'));
            $SQL->addUpdate('receipt_request_date', $Receipt->get('request_date'));
            $SQL->addUpdate('receipt_request_time', $Receipt->get('request_time'));
            $SQL->addUpdate('receipt_request_others', $Receipt->get('request_others'));
            $SQL->addUpdate('receipt_charge_payment', $Receipt->get('charge_payment'));
            $SQL->addUpdate('receipt_charge_deliver', $Receipt->get('charge_deliver'));
            $SQL->addUpdate('receipt_charge_others', $Receipt->get('charge_others'));
            $SQL->addUpdate('receipt_updated_datetime', date('Y-m-d H:i:s'));
            $SQL->addUpdate('receipt_note', $Receipt->get('note'));
            $SQL->addWhereOpr('receipt_blog_id', BID);
            $SQL->addWhereOpr('receipt_code', $_GET['c']);
            $res = $DB->query($SQL->get(dsn()), 'exec');
        }

        return $this->Post;
    }
}
