<?php

class ACMS_POST_User_Index_Sort extends ACMS_POST
{
    function post()
    {
        $this->Post->setMethod('user', 'operative', sessionWithAdministration());
        $this->Post->setMethod('checks', 'required');
        $this->Post->validate(new ACMS_Validator());

        if ( $this->Post->isValidAll() ) {
            $DB     = DB::singleton(dsn());
            foreach ( $this->Post->getArray('checks') as $uid ) {
                if ( !($uid = intval($uid)) ) continue;
                if ( !($sort = intval($this->Post->get('sort-'.$uid))) ) $sort = 1;

                $SQL    = SQL::newUpdate('user');
                $SQL->setUpdate('user_sort', $sort);
                $SQL->addWhereOpr('user_id', $uid);
                $SQL->addWhereOpr('user_blog_id', BID);
                $DB->query($SQL->get(dsn()), 'exec');
                ACMS_RAM::user($uid, null);
            }
        }

        return $this->Post;
    }
}
