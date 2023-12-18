<?php

class ACMS_POST_Shop_Receipt_Status extends ACMS_POST
{
    function post()
    {
        $aryCode    = $this->Post->getArray('checks');
        $status     = $this->Post->get('status');
        $this->Post->validate();

        if ( $this->Post->isValidAll() ) {
            $DB = DB::singleton(dsn());
            while ( !!($code = array_shift($aryCode)) ) {
                $SQL    = SQL::newUpdate('shop_receipt');
                $SQL->addUpdate('receipt_status', $status);
                $SQL->addUpdate('receipt_updated_datetime', date('Y-m-d H:i:s'));
                $SQL->addWhereOpr('receipt_code', $code);
                $SQL->addWhereOpr('receipt_blog_id', BID);
                $DB->query($SQL->get(dsn()), 'exec');
            }
        }
        return $this->Post;
    }
}