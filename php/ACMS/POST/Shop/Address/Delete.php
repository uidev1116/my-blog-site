<?php

class ACMS_POST_Shop_Address_Delete extends ACMS_POST_Shop
{
    function post()
    {
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newDelete('shop_address');
        $SQL->addWhereOpr('address_id', $this->Post->get('aid'));
        $SQL->addWhereOpr('address_user_id', SUID);
        $DB->query($SQL->get(dsn()), 'exec');

        return $this->Post;
    }
}
