<?php

class ACMS_POST_Entry_Index_Category extends ACMS_POST
{
    function post()
    {
        if ( !($cid = intval($this->Post->get('cid'))) ) $cid = null;
        $this->Post->setMethod('checks', 'required');

        if ( enableApproval(BID, CID) ) {
            $this->Post->setMethod('entry', 'operable', sessionWithApprovalAdministrator(BID, CID));
        } else if ( roleAvailableUser() ) {
            $this->Post->setMethod('entry', 'operable', roleAuthorization('entry_edit', BID));
        } else {
            $this->Post->setMethod('entry', 'operable', sessionWithContribution());
        }

        $this->Post->validate(new ACMS_Validator());

        if ( $entryArray = $this->checkCategory($this->Post->getArray('checks'), $cid) ){
            $this->Post->set('error_entries', $entryArray);
            return $this->Post;
        }

        if ( $this->Post->isValidAll() ) {
            $DB     = DB::singleton(dsn());
            foreach ( $this->Post->getArray('checks') as $beid ) {
                $id     = preg_split('@:@', $beid, 2, PREG_SPLIT_NO_EMPTY);
                $bid    = $id[0];
                $eid    = $id[1];

                if ( !($eid = intval($eid)) ) continue;
                if ( !($bid = intval($bid)) ) continue;

                $SQL    = SQL::newUpdate('entry');
                $SQL->setUpdate('entry_category_id', $cid);
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

    function checkCategory($checked, $cid)
    {
        if ( is_null($cid) ) return false;

        $error          = false;
        $discovery      = false;
        $entries        = array();

        foreach ( $checked as $beid ) {
            $id     = preg_split('@:@', $beid, 2, PREG_SPLIT_NO_EMPTY);
            $bid    = $id[0];
            $eid    = $id[1];

            if ( !($eid = intval($eid)) ) continue;
            if ( !($bid = intval($bid)) ) continue;

            $categoryBlog   = intval(ACMS_RAM::categoryBlog($cid));
            $categoryScope  = ACMS_RAM::categoryScope($cid);

            if ( $categoryScope === 'local' ) {
                if ( $bid !== $categoryBlog ) {
                    $error  = true;
                    $entries[]  = $eid;
                }
            } else {
                $currentBid = $bid;
                do {
                    if ( $categoryBlog === $currentBid) {
                        $discovery  = true;
                        break;
                    }
                    $currentBid = intval(ACMS_RAM::blogParent($currentBid));
                } while ( intval(ACMS_RAM::blogParent($currentBid)) !== 0 );

                if ( $categoryBlog === $currentBid) {
                    $discovery  = true;
                }

                if ( !$discovery ) {
                    $error      = true;
                    $entries[]  = $eid;
                    $discovery  = false;
                }
            }
        }

        return ( $error ) ? $entries : false;
    }
}
