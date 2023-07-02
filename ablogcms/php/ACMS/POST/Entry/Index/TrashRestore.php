<?php

class ACMS_POST_Entry_Index_TrashRestore extends ACMS_POST_Trash
{
    function post()
    {
        $this->Post->reset(true);
        $this->Post->setMethod('checks', 'required');

        if (config('approval_contributor_edit_auth') !== 'on' && enableApproval(BID, CID)) {
            $this->Post->setMethod('entry', 'operative', sessionWithApprovalAdministrator(BID, CID));
        } else if ( roleAvailableUser() ) {
            $this->Post->setMethod('entry', 'operative', roleAuthorization('entry_delete', BID));
        } else {
            $this->Post->setMethod('entry', 'operative', sessionWithContribution());
        }
        $this->Post->validate(new ACMS_Validator());

        if ( $this->Post->isValidAll() ) {
            @set_time_limit(0);
            foreach ( $this->Post->getArray('checks') as $eid ) {
                $id     = preg_split('@:@', $eid, -1, PREG_SPLIT_NO_EMPTY);
                $eid    = $id[1];
                $this->restore($eid);

                // $this->redirect(acmsLink(array(
                //     'bid'   => $bid,
                //     'eid'   => $eid,
                // )));
            }
        }
        return $this->Post;
    }
}
