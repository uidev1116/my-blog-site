<?php

class ACMS_POST_Comment_Edit extends ACMS_POST_Comment
{
    function post()
    {
        $Comment    =& $this->Post->getChild('comment');
        $Comment->setMethod('comment', 'cmidIsNull', !!CMID);

        if ( $this->Post->isValidAll() ) {
            if (
                $suid   = intval(SUID)
                and ( 0
                    or sessionWithCompilation()
                    or ACMS_RAM::entryUser(EID) == $suid
                    or ACMS_RAM::commentUser(CMID) == $suid 
                )
            ) {
                $Comment->setField('name', ACMS_RAM::commentName(CMID));
                $Comment->setField('mail', ACMS_RAM::commentMail(CMID));
                $Comment->setField('url', ACMS_RAM::commentUrl(CMID));
                $Comment->setField('title', ACMS_RAM::commentTitle(CMID));
                $Comment->setField('body', ACMS_RAM::commentBody(CMID));
                $Comment->setField('pass', ACMS_RAM::commentPass(CMID));

                $this->Post->set('action', 'update');
                $this->Post->set('step', 'reapply');
            } else {
                $this->Post->set('action', 'auth');
                $this->Post->set('step', 'auth');
            }
        }

        return $this->Post;
    }
}
