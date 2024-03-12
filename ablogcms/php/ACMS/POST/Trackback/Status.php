<?php

class ACMS_POST_Trackback_Status extends ACMS_POST
{
    public $_status    = 'open';

    function post()
    {
        $this->Post->setMethod('trackback', 'operative', !!sessionWithCompilation());
        $this->Post->setMethod('trackback', 'tbidIsNull', !!TBID);
        $this->Post->validate(new ACMS_Validator());

        if ($this->Post->isValid()) {
            $DB     = DB::singleton(dsn());
            $SQL    = SQL::newUpdate('trackback');
            $SQL->setUpdate('trackback_status', $this->_status);
            $SQL->addWhereOpr('trackback_id', TBID);
            $DB->query($SQL->get(dsn()), 'exec');
        }

        return $this->Post;
    }
}
