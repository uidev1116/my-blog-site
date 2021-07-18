<?php

class ACMS_GET_Admin_Entry_Revision_Index extends ACMS_GET_Admin_Entry
{
    function get()
    {
        if ( !sessionWithContribution() ) return 'Bad Access.';
        if ( !defined('EID') ) return '';

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $vars   = array();

        $DB     = DB::singleton(dsn());

        $SQL    = SQL::newSelect('entry');
        $SQL->addSelect('entry_current_rev_id');
        $SQL->addWhereOpr('entry_id', EID);
        $SQL->addWhereOpr('entry_blog_id', BID);
        $currentRvid = $DB->query($SQL->get(dsn()), 'one');
        $currentRvid = intval($currentRvid);
       
        $SQL    = SQL::newSelect('entry_rev');
        $SQL->addWhereOpr('entry_id', EID);
        $SQL->addWhereOpr('entry_blog_id', BID);
        $SQL->setOrder('entry_rev_datetime', 'desc');

        $revisionTemp   = array();
        $revisionExists = false;

        if ( $revisionAry = $DB->query($SQL->get(dsn()), 'all') ) {
            foreach ( $revisionAry as $rev ) {
                $auid   = $rev['entry_rev_user_id'];
                $author = ACMS_RAM::user($auid);
                $rvid   = intval($rev['entry_rev_id']);

                $revision = array(
                    'rvid'          => $rvid,
                    'memo'          => $rev['entry_rev_memo'],
                    'status'        => $rev['entry_status'],
                    'rev_status'    => $rev['entry_rev_status'],
                    'author'        => $author['user_name'],
                    'icon'          => loadUserIcon($auid),
                    'datetime'      => $rev['entry_rev_datetime'],
                    'confirmUrl'    => acmsLink(array(
                        'bid'   => BID,
                        'eid'   => EID,
                        'cid'   => CID,
                        'aid'   => $this->Get->get('aid'),
                        'query' => array(
                            'rvid'  => $rev['entry_rev_id'],
                            'aid'   => $this->Get->get('aid'),
                        ),
                    )),
                    'dupEditUrl'    => acmsLink(array(
                        'bid'   => BID,
                        'eid'   => EID,
                        'admin' => 'entry-edit',
                        'query' => array(
                            'rvid'  => $rev['entry_rev_id'],
                        ),
                    )),
                );

                if ( $currentRvid === $rvid ) {
                    $revision['checked']        = config('attr_checked');
                    $revision['current']        = ' class="acms-table-info"';
                }

                if ( $rvid === 1 ) {
                    $revisionTemp = $revision;
                    continue;
                }

                if ( 1
                    && $rvid !== 1 
                    && $currentRvid !== $rvid
                ) {
                    $Tpl->add(array('delete', 'revision:loop'), array(
                        '_rvid'     => $rvid,
                    ));
                }
                if ( empty($rev['entry_rev_status']) ) {
                    $rev['entry_rev_status'] = 'none';
                }

                if ( $rev['entry_status'] === 'trash' && $rev['entry_rev_status'] === 'in_review' ) {
                    $Tpl->add(array('touch:rev_status#trash', 'revision:loop'));
                } else {
                    $Tpl->add(array('touch:rev_status#'.$rev['entry_rev_status'], 'revision:loop'));
                }
                $Tpl->add(array('touch:status#'.$rev['entry_status'], 'revision:loop'));
                $Tpl->add('revision:loop', $revision);

                $revisionExists = true;
            }
            if ( !empty($revisionTemp) ) {
                $Tpl->add(array('touch:status#'.$revisionTemp['status'], 'revisionTmp'));
                $Tpl->add('revisionTmp', $revisionTemp);
            }
        } else {
            $Tpl->add('revision#notFound');
            $Tpl->add(null, $vars);
            return $Tpl->get();
        }

        if ( !$revisionExists ) {
            $Tpl->add('revision#notExists');
        }

        $Tpl->add(null, $vars);
        return $Tpl->get();
    }
}
