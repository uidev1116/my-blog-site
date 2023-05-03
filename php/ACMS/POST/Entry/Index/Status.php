<?php

class ACMS_POST_Entry_Index_Status extends ACMS_POST
{
    function post()
    {
        if (config('approval_contributor_edit_auth') !== 'on' && enableApproval(BID, CID)) {
            $this->Post->setMethod('entry', 'operative', sessionWithApprovalAdministrator(BID, CID));
        } else if ( roleAvailableUser() ) {
            $this->Post->setMethod('entry', 'operative', roleAuthorization('entry_edit', BID));
        } else {
            $this->Post->setMethod('entry', 'operative', sessionWithContribution());
        }
        $this->Post->setMethod('checks', 'required');
        $this->Post->setMethod('status', 'required');
        $this->Post->setMethod('status', 'in', array('open', 'close', 'draft'));
        $this->Post->validate(new ACMS_Validator());

        if ( $this->Post->isValidAll() ) {
            $DB     = DB::singleton(dsn());
            $status = $this->Post->get('status');
            foreach ( $this->Post->getArray('checks') as $eid ) {
                $id     = preg_split('@:@', $eid, 2, PREG_SPLIT_NO_EMPTY);
                $bid    = $id[0];
                $eid    = $id[1];
                if ( !($eid = intval($eid)) ) continue;
                if ( !($bid = intval($bid)) ) continue;

                $SQL    = SQL::newUpdate('entry');
                $SQL->setUpdate('entry_status', $status);
                $SQL->addWhereOpr('entry_id', $eid);
                $SQL->addWhereOpr('entry_blog_id', $bid);
                if ( !sessionWithCompilation() && !roleAuthorization('entry_edit_all') ) {
                    $SQL->addWhereOpr('entry_user_id', SUID);
                }
                $DB->query($SQL->get(dsn()), 'exec');
                ACMS_RAM::entry($eid, null);

            }
        }

        return $this->Post;
    }
}
