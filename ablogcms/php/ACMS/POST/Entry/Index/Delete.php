<?php

class ACMS_POST_Entry_Index_Delete extends ACMS_POST_Entry_Delete
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
            $DB     = DB::singleton(dsn());
            foreach ( $this->Post->getArray('checks') as $eid ) {
                $id     = preg_split('@:@', $eid, 2, PREG_SPLIT_NO_EMPTY);
                $eid    = $id[1];
                if ( roleAvailableUser() ) {
                    if ( !( 1
                        and !!($eid = intval($eid))
                        and !!($ebid = ACMS_RAM::entryBlog($eid))
                        and roleAuthorization('entry_delete', $ebid, $eid)
                    ) ) continue;
                } else {
                    if ( !( 1
                        and !!($eid = intval($eid))
                        and !!($ebid = ACMS_RAM::entryBlog($eid))
                        and ACMS_RAM::blogLeft(SBID) <= ACMS_RAM::blogLeft($ebid)
                        and ACMS_RAM::blogRight(SBID) >= ACMS_RAM::blogRight($ebid)
                        and ( 0
                            or sessionWithCompilation()
                            or (SUID == ACMS_RAM::entryUser($eid))
                        )
                    ) ) continue;
                }
                $this->delete($eid);
            }
        }

        return $this->Post;
    }
}
