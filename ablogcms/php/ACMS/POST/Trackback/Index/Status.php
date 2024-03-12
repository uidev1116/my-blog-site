<?php

class ACMS_POST_Trackback_Index_Status extends ACMS_POST
{
    function post()
    {
        $this->Post->setMethod('trackback', 'operative', sessionWithCompilation());
        $this->Post->setMethod('checks', 'required');
        $this->Post->setMethod('status', 'required');
        $this->Post->setMethod('status', 'in', array('open', 'close', 'awaiting'));
        $this->Post->validate(new ACMS_Validator());

        if ($this->Post->isValidAll()) {
            $DB     = DB::singleton(dsn());
            foreach ($this->Post->getArray('checks') as $cmid) {
                $SQL    = SQL::newUpdate('trackback');
                $SQL->setUpdate('trackback_status', $this->Post->get('status'));
                $SQL->addWhereOpr('trackback_id', intval($cmid));
                $SQL->addWhereOpr('trackback_blog_id', BID);
                $DB->query($SQL->get(dsn()), 'exec');
            }
        }

        return $this->Post;
    }
}
