<?php

class ACMS_GET_Admin_Entry_Revision_Info extends ACMS_GET_Admin_Entry
{
    function get()
    {
        if ( !sessionWithContribution(BID, false) ) return 'Bad Access.';
        if ( !EID ) return '';
        if ( !RVID ) return '';

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $vars   = array();

        if ( roleAvailableUser() ) {
            if ( 0
                || ( enableApproval(BID, CID) && sessionWithApprovalPublic(BID, CID) )
                || ( !enableApproval(BID, CID) && roleAuthorization('entry_edit', BID, EID) )
            ) {
                $Tpl->add('revisionChange');
            }
        } else if ( enableApproval(BID, CID) ) {
            if ( sessionWithApprovalPublic(BID, CID) ) {
                $Tpl->add('revisionChange');
            }
        } else {
            do {
                if ( !sessionWithCompilation(BID, false) ) {
                    if ( !sessionWithContribution(BID, false) ) break;
                    if ( SUID <> ACMS_RAM::entryUser(EID) ) break;
                }
                $Tpl->add('revisionChange');
            } while ( false );
        }

        $DB     = DB::singleton(dsn());

        $SQL    = SQL::newSelect('entry_rev');
        $SQL->addSelect('entry_rev_user_id');
        $SQL->addSelect('entry_rev_datetime');
        $SQL->addSelect('entry_rev_status');
        $SQL->addSelect('entry_status');
        $SQL->addSelect('entry_rev_memo');
        $SQL->addWhereOpr('entry_id', EID);
        $SQL->addWhereOpr('entry_rev_id', RVID);
        $SQL->addWhereOpr('entry_blog_id', BID);
        
        if ( $row = $DB->query($SQL->get(dsn()), 'row') ) {
            $auid   = $row['entry_rev_user_id'];
            $author = ACMS_RAM::user($auid);

            $status = '承認前';
            switch ( $row['entry_rev_status'] ) {
                case 'in_review':
                    $status = '承認中';
                    break;
                case 'reject':
                    $status = '承認却下';
                    break;
                case 'approved':
                    $status = '承認済み';
                    break;
                default:
                    $status = '承認前';
                    break;
            }
            if ( $row['entry_status'] === 'trash' ) {
                $status .= ' 削除依頼';
            }

            $vars = array(
                'rvid'          => RVID,
                'memo'          => $row['entry_rev_memo'],
                'author'        => $author['user_name'],
                'icon'          => loadUserIcon($auid),
                'datetime'      => $row['entry_rev_datetime'],
                'url'           => acmsLink(array(
                    'eid'   => EID,
                    'bid'   => BID,
                    'aid'   => $this->Get->get('aid', null),
                    'query' => array(
                        'rvid'  => RVID,
                        'trash' => 'show',
                    ),
                )),
            );
            if ( enableApproval(BID, CID) ) {
                $vars['status'] = $status;
            }
        }
        $Tpl->add(null, $vars);

        return $Tpl->get();
    }
}
