<?php

class ACMS_POST_Revision_Change extends ACMS_POST_Entry
{
    function post()
    {
        if ( !EID ) die();
        if ( !IS_LICENSED ) die();

        if ( enableApproval(BID, CID) ) {
            if ( !sessionWithApprovalAdministrator(BID, CID) ) die();
        } else if ( roleAvailableUser() ) {
            if ( !roleAuthorization('entry_edit', BID, EID) ) die();
        } else {
            if ( !sessionWithCompilation(BID, false) ) {
                if ( !sessionWithContribution(BID, false) ) die();
                if ( SUID <> ACMS_RAM::entryUser(EID) ) die();
            }
        }

        $rvid = $this->Post->get('revision');
        if ( !is_numeric($rvid) ) die();

        $cid = Entry::changeRevision($rvid, EID, BID);

        $this->redirect(acmsLink(array(
            'bid'   => BID,
            'eid'   => EID,
            'cid'   => $cid,
        )));
    }
}
