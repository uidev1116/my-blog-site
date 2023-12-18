<?php

class ACMS_POST_Shop_Receipt_Delete extends ACMS_POST_Shop
{   
    function post()
    {
        if ( !sessionWithAdministration() ) return die();

        $DB     = DB::singleton(dsn());

        $SQL = SQL::newDelete('shop_receipt');
        $SQL->addWhereOpr('receipt_code', $_GET['c']);
        $SQL->addWhereOpr('receipt_blog_id', BID);

        if ( $DB->query($SQL->get(dsn()), 'exec') ) {
            $SQL = SQL::newDelete('shop_receipt_detail');
            $SQL->addWhereOpr('receipt_detail_parent_code', $_GET['c']);
            $DB->query($SQL->get(dsn()), 'exec');
        }

        return $this->Post;
    }
}