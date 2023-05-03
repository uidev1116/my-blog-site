<?php

class ACMS_POST_Api_Twitter_Entry_Open extends ACMS_POST_Entry_Open
{
    function post ()
    {
        $this->Post->reset(true);
        $this->Post->setMethod('entry', 'operable', (1
            and !!($eid = intval($this->Post->get('eid')))
            and !!IS_LICENSED
            and ( 0
                or sessionWithCompilation()
                or ( 1
                    and sessionWithContribution()
                    and SUID == ACMS_RAM::entryUser($eid)
                )
            )
        ));
        $this->Post->validate();

        if ( $this->Post->isValidAll() ) {
            $bid    = $this->Post->get('bid');
            $cid    = $this->Post->get('cid');
            $eid    = $this->Post->get('eid');

            $title  = ACMS_RAM::entryTitle($eid);
            $url    = acmsLink(array('bid'=>$bid, 'cid'=>$cid, 'eid'=>$eid));
            $tweet  = $this->Post->get('tweet');

            ACMS_POST_Api_Twitter_Statuses_Update::tweet($tweet, $bid, true);
        }

        return parent::post();
    }
}
