<?php

class ACMS_POST_Trackback_Delete extends ACMS_POST
{
    function post()
    {
        $this->Post->setMethod('trackback', 'operative', !!sessionWithCompilation());
        $this->Post->setMethod('trackback', 'tbidIsNull', !!TBID);
        $this->Post->validate(new ACMS_Validator());

        if ( $this->Post->isValid() ) {
            $DB     = DB::singleton(dsn());
            $SQL    = SQL::newDelete('trackback');
            $SQL->addWhereOpr('trackback_id', TBID);
            $DB->query($SQL->get(dsn()), 'exec');

            $this->redirect(acmsLink(array('eid' => EID,)));
        }

        return $this->Post;
    }
}
