<?php

class ACMS_POST_Module_Index_Status extends ACMS_POST
{
    function post()
    {
        if ( enableApproval(BID, CID) ) {
            $this->Post->setMethod('module', 'operative', sessionWithApprovalAdministrator(BID, CID));
        } else if ( roleAvailableUser() ) {
            $this->Post->setMethod('module', 'operative', roleAuthorization('entry_edit', BID));
        } else {
            $this->Post->setMethod('module', 'operative', sessionWithAdministration());
        }

        $this->Post->setMethod('checks', 'required');
        $this->Post->setMethod('status', 'required');
        $this->Post->setMethod('status', 'in', array('open', 'close'));
        $this->Post->validate(new ACMS_Validator());

        if ( $this->Post->isValidAll() ) {
            $DB     = DB::singleton(dsn());
            $status = $this->Post->get('status');
            foreach ( $this->Post->getArray('checks') as $mid ) {
                $id     = preg_split('@:@', $mid, 2, PREG_SPLIT_NO_EMPTY);
                $bid    = $id[0];
                $mid    = $id[1];
                if ( !($mid = intval($mid)) ) continue;
                if ( !($bid = intval($bid)) ) continue;

                if ( !( 0
                    or sessionWithAdministration($bid)
                    or ( roleAvailableUser() and roleAuthorization('module_edit', $bid) )
                    or Auth::checkShortcut('Module_Update', ADMIN, 'mid', $mid)
                ) ) {
                    continue;
                }

                $SQL    = SQL::newUpdate('module');
                $SQL->setUpdate('module_status', $status);
                $SQL->addWhereOpr('module_id', $mid);
                $SQL->addWhereOpr('module_blog_id', $bid);
                $DB->query($SQL->get(dsn()), 'exec');
            }
            $this->Post->set('refreshed', 'refreshed');
        }

        return $this->Post;
    }
}
