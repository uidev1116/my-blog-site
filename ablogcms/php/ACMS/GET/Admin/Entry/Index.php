<?php

class ACMS_GET_Admin_Entry_Index extends ACMS_GET_Admin_Entry
{
    var $_scope = array(
        'field'     => 'global',
    );

    function get()
    {
        if ( !sessionWithContribution() ) return '';

        $status = ite($_GET, 'status');
        $order  = ORDER ? ORDER : 'datetime-desc';
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
        if ( sessionWithCompilation() || roleAuthorization('entry_edit_all')  ) $Tpl->add('userSelect#filter');

        //----------
        // init SQL
        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('entry');
        $lockService = App::make('entry.lock');

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
        $SQL->addWhereOpr('entry_status', 'trash', '<>');
        if ( !empty($status) ) {
            $SQL->addWhereOpr('entry_status', $status);
            $vars['status:selected#'.$status]    = config('attr_selected');
        }

        //---------
        // session
        $session = $this->Get->get('session');
        switch ( $session ) {
            case 'public':
                $SQL->addWhereOpr('entry_start_datetime', date('Y-m-d H:i:s', REQUEST_TIME), '<=');
                $SQL->addWhereOpr('entry_end_datetime', date('Y-m-d H:i:s', REQUEST_TIME), '>=');
                break;
            case 'expiration':
                $SQL->addWhereOpr('entry_end_datetime', date('Y-m-d H:i:s', REQUEST_TIME), '<');
                break;
            case 'future':
                $SQL->addWhereOpr('entry_start_datetime', date('Y-m-d H:i:s', REQUEST_TIME), '>=');
                break;
        }
        $vars['session:selected#'.$session] = config('attr_selected');

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
        // field
        if ( !$this->Field->isNull() ) {
            ACMS_Filter::entryField($SQL, $this->Field);
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

        if ($axis === 'self') {
            $SQL->addWhereOpr('entry_blog_id', $target_bid);
        } else {
            $SQL->addLeftJoin('blog', 'blog_id', 'entry_blog_id');
            ACMS_Filter::blogTree($SQL, $target_bid, $axis);
        }
        $category_axis = $this->Get->get('category_axis', 'self');
        $Tpl->add('category_axis', array(
            'category_axis:checked#'.$category_axis => config('attr_checked')
        ));
        if ( CID ) {
            if ( 1 >= ACMS_RAM::categoryRight(CID) - ACMS_RAM::categoryLeft(CID) ) {
                $category_axis = 'self';
            }
            $SQL->addLeftJoin('category', 'category_id', 'entry_category_id');
            ACMS_Filter::categoryTree($SQL, CID, $category_axis);
            ACMS_Filter::categoryStatus($SQL);
        } else if ( $this->Get->get('_cid') === '0' ) {
            $SQL->addWhereOpr('entry_category_id', null);
            $vars['non_category#selected'] = config('attr_selected');
        }

        //-------------
        // contributor
        if (roleAvailableUser()) {
            $UID = !roleAuthorization('entry_edit_all', BID) ? SUID : UID;
        } else {
            if (!sessionWithCompilation() && (config('approval_contributor_edit_auth') === 'on' || !enableApproval(BID, CID))) {
                $UID = SUID;
            } else {
                $UID = UID;
            }
        }
        if ( $UID ) $SQL->addWhereOpr('entry_user_id', $UID);

        $Pager  = new SQL_Select($SQL);
        $Pager->setSelect('DISTINCT(entry_id)', 'entry_amount', null, 'count');
        if ( !$pageAmount = intval($DB->query($Pager->get(dsn()), 'one')) ) {
            $Tpl->add('index#notFound');
            $vars['notice_mess'] = 'show';
            $Tpl->add(null, $vars);
            return $Tpl->get();
        }

        //-------------
        // sort#header
        if ( 1
            && ( $order === 'sort-asc' || $order === 'sort-desc' )
            && !KEYWORD
            && empty($status)
            && $axis === 'self'
            && $category_axis === 'self'
        ) {
            if ( $UID && !CID ) {
                $Tpl->add('sort#headerUser');
                $vars['postSortType'] = 'ACMS_POST_Entry_Index_Sort_User';
            } else if ( !$UID && CID ) {
                $Tpl->add('sort#headerCategory');
                $vars['postSortType'] = 'ACMS_POST_Entry_Index_Sort_Category';
            } else if ( !$UID && !CID ) {
                $Tpl->add('sort#header');
                $vars['postSortType'] = 'ACMS_POST_Entry_Index_Sort_Entry';
            }
            $vars['sortable'] = 'on';
        } else {
            $vars['sortable'] = 'off';
        }

        if ( ADMIN === 'entry_index' ) {
            $query = array('admin' => ADMIN);
        } else {
            $query = array();
        }
        $vars   += $this->buildPager(PAGE, $limit, $pageAmount
            , config('admin_pager_delta'), config('admin_pager_cur_attr'), $Tpl, array(), $query
        );

        $SQL->setLimit($limit, (PAGE - 1) * $limit);
        $orderInfo = explode('-', $order);
        ACMS_Filter::entryOrder($SQL, array($order, 'id-' . $orderInfo[1]), $UID, CID);
        if (isset($orderInfo[0])) {
            $SQL->addGroup('entry_' . $orderInfo[0]);
        }
        $SQL->addGroup('entry_id');

        $q = $SQL->get(dsn());
        $DB->query($q, 'fetch');
        $entryIds = array();
        $entryArray = array();
        while ($row = $DB->fetch($q)) {
            $entryArray[] = $row;
            $entryIds[] = intval($row['entry_id']);
        };
        $eagerLoadField = eagerLoadField($entryIds, 'eid');

        foreach ($entryArray as $row) {
            $eid    = $row['entry_id'];
            $cid    = $row['entry_category_id'];
            $uid    = $row['entry_user_id'];
            $bid    = $row['entry_blog_id'];

            $_vars   = array();
            $_vars   += array(
                'eid'       => $eid,
                'bid'       => $bid,
                'datetime'  => $row['entry_datetime'],
                'updated_datetime' => $row['entry_updated_datetime'],
                'posted_datetime' => $row['entry_posted_datetime'],
                'title'     => addPrefixEntryTitle($row['entry_title']
                    , $row['entry_status']
                    , $row['entry_start_datetime']
                    , $row['entry_end_datetime']
                    , $row['entry_approval']
                ),
                'code'      => $row['entry_code'],
                'blogName'  => ACMS_RAM::blogName($bid),
                'userName'  => ACMS_RAM::userName($uid),
                'userIcon'  => loadUserIcon($uid),
                'entryUrl'  => acmsLink(array(
                    'bid'   => $bid,
                    'eid'   => $eid,
                    'query' => array(),
                )),
                'blogUrl'   => acmsLink(array(
                    'admin' => ADMIN,
                    'bid'   => $bid,
                    'query' => array(),
                )),
                'userUrl'   => acmsLink(array(
                    'admin' => ADMIN,
                    'bid'   => $bid,
                    'uid'   => $uid,
                    'query' => array(),
                )),
                'editUrl'   => acmsLink(array(
                    'admin' => 'entry_editor',
                    'bid'   => $bid,
                    'eid'   => $eid,
                    'query' => array(),
                ), false),
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

            //-----------
            // Lock User
            if ($lockUid = $row['entry_lock_uid']) {
                if ($lockService->getExpiredDatetime() < strtotime($row['entry_lock_datetime'])) {
                    $_vars['lockUser'] = ACMS_RAM::userName($lockUid);
                }
                if (intval($lockUid) === SUID) {
                    $_vars['selfLock'] = 'yes';
                }
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

                $_vars += array(
                    'sort' => $sort,
                );
            }
            $_vars['sort#eid'] = $eid;

            //---------
            // delete
            do {
                if (config('approval_contributor_edit_auth') !== 'on' && enableApproval(BID, CID)) {
                    if (!sessionWithApprovalAdministrator(BID, CID)) break;
                } else if ( roleAvailableUser() ) {
                    if ( !roleAuthorization('entry_delete', BID, $eid) ) break;
                }
                $Tpl->add(array('adminDeleteActionLoop', 'entry:loop'));
            } while ( false );

            //-------
            // field
            if (isset($eagerLoadField[$eid])) {
                $_vars += $this->buildField($eagerLoadField[$eid], $Tpl, 'entry:loop', 'entry');
            }

            $Tpl->add('status#'.$row['entry_status']);
            $Tpl->add('entry:loop', $_vars);
        }
        do {
            if (config('approval_contributor_edit_auth') !== 'on' && enableApproval(BID, CID)) {
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
