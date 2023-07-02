<?php

class ACMS_POST_Entry_Trash extends ACMS_POST_Trash
{
    function post()
    {
        if ( !$eid = idval($this->Post->get('eid')) ) die();
        if ( !IS_LICENSED ) die();

        if ( enableApproval(BID, CID) ) {
            $entry  = ACMS_RAM::entry($eid);
            if ( 1
                && $entry['entry_approval'] !== 'pre_approval'
                && !sessionWithApprovalAdministrator(BID, CID)
            ) {
                die();
            }
        } else if ( roleAvailableUser() ) {
            if ( !roleAuthorization('entry_delete', BID, $eid) ) die();
        } else {
            if ( !sessionWithCompilation() ) {
                if ( !sessionWithContribution() ) die();
                if ( SUID <> ACMS_RAM::entryUser($eid) ) die();
            }
        }
        if (HOOK_ENABLE) {
            Webhook::call(BID, 'entry', 'entry:deleted', array($eid, null));
        }

        $this->trash($eid);

        $this->redirect(acmsLink(array(
            'bid'   => BID,
            'cid'   => CID,
        )));
    }
}
