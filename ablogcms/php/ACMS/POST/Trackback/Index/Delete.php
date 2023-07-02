<?php

class ACMS_POST_Trackback_Index_Delete extends ACMS_POST
{
    function post()
    {
        $this->Post->setMethod('trackback', 'operative', sessionWithCompilation());
        $this->Post->validate(new ACMS_Validator());

        if ( $this->Post->isValidAll() ) {
            $DB     = DB::singleton(dsn());
            foreach ( $this->Post->getArray('checks') as $cmid ) {
                $SQL    = SQL::newDelete('trackback');
                $SQL->addWhereOpr('trackback_id', intval($cmid));
                $SQL->addWhereOpr('trackback_blog_id', BID);
                $DB->query($SQL->get(dsn()), 'exec');
            }
        }

        return $this->Post;
    }
}
