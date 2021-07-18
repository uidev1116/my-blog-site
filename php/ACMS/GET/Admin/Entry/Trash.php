<?php

class ACMS_GET_Admin_Entry_Trash extends ACMS_GET_Admin_Entry
{
    function get()
    {
        if ( 'entry_trash' <> ADMIN ) return '';
        if ( !sessionWithContribution() ) return '';

        $status = ite($_GET, 'status');
        $order  = ORDER ? ORDER : 'updated_datetime-desc';
        $limits = configArray('admin_limit_option');
        $limit  = LIMIT ? LIMIT : $limits[config('admin_limit_default')];

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $vars   = array();

        //-------
        // error
        if ( $entries = $this->Post->getArray('error_entries') ) {
            $Tpl->add('errorMessage');
            $vars['notice_mess'] = 'show';
            foreach ( $entries as $id ) {
                $Tpl->add('errorEid:loop', array(
                    'errorEid'  => $id,
                ));
            }
        } else {
            //---------
            // refresh
            if ( !$this->Post->isNull() ) {
                $Tpl->add('refresh');
                $vars['notice_mess'] = 'show';
                $notice = true;
            }
        }

        //------------
        // userSelect
        if ( sessionWithCompilation() ) $Tpl->add('userSelect#filter');

        //----------
        // init SQL
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('entry');

        //-----
        // bid
        $target_bid = $this->Get->get('_bid', BID);

        //------
        // axis
        $axis   = $this->Get->get('axis', 'self');
        if ( 1 < ACMS_RAM::blogRight($target_bid) - ACMS_RAM::blogLeft($target_bid) ) {
            $Tpl->add('axis', array(
                'axis:checked#'.$axis => config('attr_checked')
            ));
        } else {
            $axis   = 'self';
        }

        //--------
        // status
        $SQL->addWhereOpr('entry_status', 'trash');

        //---------
        // keyword
        if ( !!KEYWORD ) {
            $SQL->addLeftJoin('fulltext', 'fulltext_eid', 'entry_id');
            $keywords = preg_split(REGEX_SEPARATER, KEYWORD, -1, PREG_SPLIT_NO_EMPTY);
            foreach ( $keywords as $keyword ) {
                $SQL->addWhereOpr('fulltext_value', '%'.$keyword.'%', 'LIKE');
            }
        }

        //-------
        // order
        $vars['order:selected#'.$order]  = config('attr_selected');

        //-------
        // limit
        foreach ( $limits as $val ) {
            $_vars  = array('limit' => $val);
            if ( $limit == $val ) $_vars['selected'] = config('attr_selected');
            $Tpl->add('limit:loop', $_vars);
        }

        $SQL->addLeftJoin('blog', 'blog_id', 'entry_blog_id');
        ACMS_Filter::blogTree($SQL, $target_bid, $axis);
        ACMS_Filter::blogStatus($SQL);

        if ( CID ) {
            $SQL->addLeftJoin('category', 'category_id', 'entry_category_id');
            ACMS_Filter::categoryTree($SQL, CID, 'descendant-or-self');
            ACMS_Filter::categoryStatus($SQL);
        } else if ( $this->Get->get('_cid') === '0' ) {
            $SQL->addWhereOpr('entry_category_id', null);
            $vars['non_category#selected'] = config('attr_selected');
        }

        //-------------
        // contributor
        if ( roleAvailableUser() ) {
            $UID    = !roleAuthorization('entry_edit_all', BID) ? SUID : UID;
        } else {
            $UID    = !sessionWithCompilation() ? SUID : UID;
        }
        if ( $UID ) $SQL->addWhereOpr('entry_user_id', $UID);

        $Pager  = new SQL_Select($SQL);
        $Pager->setSelect('*', 'entry_amount', null, 'count');
        if ( !$pageAmount = intval($DB->query($Pager->get(dsn()), 'one')) ) {
            $Tpl->add('index#notFound');
            $vars['notice_mess'] = 'show';
            $Tpl->add(null, $vars);
            return $Tpl->get();
        }

        $vars   += $this->buildPager(PAGE, $limit, $pageAmount
            , config('admin_pager_delta'), config('admin_pager_cur_attr'), $Tpl, array(), array('admin' => ADMIN)
        );

        $SQL->setLimit($limit, (PAGE - 1) * $limit);
        ACMS_Filter::entryOrder($SQL, $order, $UID, CID);

        $q  = $SQL->get(dsn());
        $DB->query($q, 'fetch');

        while ( $row = $DB->fetch($q) ) {
            $eid    = $row['entry_id'];
            $cid    = $row['entry_category_id'];
            $uid    = $row['entry_user_id'];
            $bid    = $row['entry_blog_id'];

            $_vars   = array();
            $_vars   += array(
                'eid'       => $eid,
                'bid'       => $bid,
                'datetime'  => $row['entry_datetime'],
                'title'     => $row['entry_title'],
                'code'      => $row['entry_code'],
                'blogName'  => ACMS_RAM::blogName($bid),
                'userName'  => ACMS_RAM::userName($uid),
                'userIcon'  => loadUserIcon($uid),
                'entryUrl'  => acmsLink(array(
                    'admin' => false,
                    'bid'   => $bid,
                    'eid'   => $eid,
                )),
                'blogUrl'   => acmsLink(array(
                    'admin' => ADMIN,
                    'bid'   => $bid,
                )),
                'userUrl'   => acmsLink(array(
                    'admin' => ADMIN,
                    'uid'   => $uid,
                )),
                'editUrl'   => acmsLink(array(
                    'admin' => 'entry_editor',
                    'eid'   => $eid,
                )),
            );

            if ( $cid ) {
                $_vars   += array(
                    'categoryName'  => ACMS_RAM::categoryName($cid),
                    'categoryUrl'   => acmsLink(array(
                        'admin' => ADMIN,
                        'cid'   => $cid,
                    )),
                );
            }

            //------------
            // sort#value
            if ( 'self' == $axis ) {
                if ( $UID ) {
                    $sort   = $row['entry_user_sort'];
                } else if ( CID ) {
                    $sort   = $row['entry_category_sort'];
                } else {
                    $sort   = $row['entry_sort'];
                }

                $_vars  += array(
                    'sort'      => $sort,
                    'sort#eid'  => $eid,
                );
            }

            //---------
            // delete
            do {
                if ( enableApproval(BID, CID) ) {
                    if ( !sessionWithApprovalAdministrator(BID, CID) ) break;
                } else if ( roleAvailableUser() ) {
                    if ( !roleAuthorization('entry_delete', BID, $eid) ) break;
                }
                $Tpl->add(array('adminDeleteActionLoop', 'entry:loop'));
            } while ( false );

            //-------
            // field
            $_vars  += $this->buildField(loadEntryField($eid), $Tpl, 'entry:loop', 'entry');

            $Tpl->add('status#'.$row['entry_status']);
            $Tpl->add('entry:loop', $_vars);
        }

        do {
            if ( enableApproval(BID, CID) ) {
                if ( !sessionWithApprovalAdministrator(BID, CID) ) break;
            } else if ( roleAvailableUser() ) {
                if ( !roleAuthorization('entry_delete', BID) ) break;
            }
            $Tpl->add(array('adminDeleteAction'));
            $Tpl->add(array('adminDeleteAction2'));
        } while ( false );

        //-------------
        // sort:action
        if ( 'self' == $axis ) {
            if ( $UID ) {
                $Tpl->add('sort:action#user');
            } else if ( CID ) {
                $Tpl->add('sort:action#category');
            } else {
                $Tpl->add('sort:action#entry');
            }
        }

        //------------
        // userSelect
        if ( sessionWithCompilation() ) $Tpl->add('userSelect#batch');


        $Tpl->add(null, $vars);
        return $Tpl->get();
    }
}
