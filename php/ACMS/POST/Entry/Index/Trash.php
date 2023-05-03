<?php

class ACMS_POST_Entry_Index_Trash extends ACMS_POST_Trash
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
            $count = count($this->Post->getArray('checks'));

            foreach ($this->Post->getArray('checks') as $eid) {
                $id     = preg_split('@:@', $eid, 2, PREG_SPLIT_NO_EMPTY);
                $bid    = $id[0];
                $eid    = $id[1];

                if ( 0
                    || !roleAvailableUser()
                    || ( roleAvailableUser() && roleAuthorization('entry_delete', $bid, $eid) )
                ) {
                    if (HOOK_ENABLE && $count === 1) {
                        Webhook::call($bid, 'entry', 'entry:deleted', array($eid, null));
                    }
                    $this->trash($eid);
                }

                // $this->redirect(acmsLink(array(
                //     'bid'   => $bid,
                //     'eid'   => $eid,
                // )));
            }
        }
        return $this->Post;
    }
}
