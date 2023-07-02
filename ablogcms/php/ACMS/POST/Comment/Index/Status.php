<?php

class ACMS_POST_Comment_Index_Status extends ACMS_POST_Comment
{
    function post()
    {
        $this->Post->setMethod('comment', 'operable', sessionWithCompilation());
        $this->Post->setMethod('checks', 'required');
        $this->Post->setMethod('status', 'required');
        $this->Post->setMethod('status', 'in', array('open', 'close', 'awaiting'));
        $this->Post->validate(new ACMS_Validator());

        if ( $this->Post->isValidAll() ) {
            $DB     = DB::singleton(dsn());
            foreach ( $this->Post->getArray('checks') as $cmid ) {
                $SQL    = SQL::newUpdate('comment');
                $SQL->setUpdate('comment_status', $this->Post->get('status'));
                $SQL->addWhereOpr('comment_id', intval($cmid));
                $SQL->addWhereOpr('comment_blog_id', BID);
                $DB->query($SQL->get(dsn()), 'exec');
            }
        }

        return $this->Post;
    }
}
