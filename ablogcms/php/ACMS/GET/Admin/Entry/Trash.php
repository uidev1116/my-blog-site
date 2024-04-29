<?php

class ACMS_GET_Admin_Entry_Trash extends ACMS_GET_Admin_Entry
{
    function get()
    {
        if ('entry_trash' <> ADMIN) {
            return '';
        }
        if (!sessionWithContribution()) {
            return '';
        }

        $status = ite($_GET, 'status');
        $order  = ORDER ? ORDER : 'updated_datetime-desc';
        $limits = configArray('admin_limit_option');
        $limit  = LIMIT ? LIMIT : $limits[config('admin_limit_default')];

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $vars   = [];

        //-------
        // error
        if ($entries = $this->Post->getArray('error_entries')) {
            $Tpl->add('errorMessage');
            $vars['notice_mess'] = 'show';
            foreach ($entries as $id) {
                $Tpl->add('errorEid:loop', [
                    'errorEid'  => $id,
                ]);
            }
        } else {
            //---------
            // refresh
            if (!$this->Post->isNull()) {
                $Tpl->add('refresh');
                $vars['notice_mess'] = 'show';
                $notice = true;
            }
        }

        //------------
        // userSelect
        if (sessionWithCompilation()) {
            $Tpl->add('userSelect#filter');
        }

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
        if (1 < ACMS_RAM::blogRight($target_bid) - ACMS_RAM::blogLeft($target_bid)) {
            $Tpl->add('axis', [
                'axis:checked#' . $axis => config('attr_checked')
            ]);
        } else {
            $axis   = 'self';
        }

        //--------
        // status
        $SQL->addWhereOpr('entry_status', 'trash');

        //---------
        // keyword
        if (!!KEYWORD) {
            $SQL->addLeftJoin('fulltext', 'fulltext_eid', 'entry_id');
            $keywords = preg_split(REGEX_SEPARATER, KEYWORD, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($keywords as $keyword) {
                $SQL->addWhereOpr('fulltext_value', '%' . $keyword . '%', 'LIKE');
            }
        }

        //-------
        // order
        $vars['order:selected#' . $order]  = config('attr_selected');

        //-------
        // limit
        foreach ($limits as $val) {
            $_vars  = ['limit' => $val];
            if ($limit == $val) {
                $_vars['selected'] = config('attr_selected');
            }
            $Tpl->add('limit:loop', $_vars);
        }

        $SQL->addLeftJoin('blog', 'blog_id', 'entry_blog_id');
        ACMS_Filter::blogTree($SQL, $target_bid, $axis);
        ACMS_Filter::blogStatus($SQL);

        if (CID) {
            $SQL->addLeftJoin('category', 'category_id', 'entry_category_id');
            ACMS_Filter::categoryTree($SQL, CID, 'descendant-or-self');
            ACMS_Filter::categoryStatus($SQL);
        } elseif ($this->Get->get('_cid') === '0') {
            $SQL->addWhereOpr('entry_category_id', null);
            $vars['non_category#selected'] = config('attr_selected');
        }

        //-------------
        // contributor
        if (roleAvailableUser()) {
            $UID    = !roleAuthorization('entry_edit_all', BID) ? SUID : UID;
        } else {
            $UID    = !sessionWithCompilation() ? SUID : UID;
        }
        if ($UID) {
            $SQL->addWhereOpr('entry_user_id', $UID);
        }

        $Pager  = new SQL_Select($SQL);
        $Pager->setSelect('*', 'entry_amount', null, 'count');
        if (!$pageAmount = intval($DB->query($Pager->get(dsn()), 'one'))) {
            $Tpl->add('index#notFound');
            $vars['notice_mess'] = 'show';
            $Tpl->add(null, $vars);
            return $Tpl->get();
        }

        $vars   += $this->buildPager(
            PAGE,
            $limit,
            $pageAmount,
            config('admin_pager_delta'),
            config('admin_pager_cur_attr'),
            $Tpl,
            [],
            ['admin' => ADMIN]
        );

        $SQL->setLimit($limit, (PAGE - 1) * $limit);
        ACMS_Filter::entryOrder($SQL, $order, $UID, CID);

        $q  = $SQL->get(dsn());
        $DB->query($q, 'fetch');

        while ($row = $DB->fetch($q)) {
            $eid    = $row['entry_id'];
            $cid    = $row['entry_category_id'];
            $uid    = $row['entry_user_id'];
            $bid    = $row['entry_blog_id'];
            $delUid = $row['entry_delete_uid'];

            $_vars = [];
            $_vars += [
                'eid'       => $eid,
                'bid'       => $bid,
                'datetime'  => $row['entry_datetime'],
                'del_datetime' => $row['entry_updated_datetime'],
                'title'     => $row['entry_title'],
                'code'      => $row['entry_code'],
                'blogName'  => ACMS_RAM::blogName($bid),
                'userName'  => ACMS_RAM::userName($uid),
                'userIcon'  => loadUserIcon($uid),
                'entryUrl'  => acmsLink([
                    'admin' => false,
                    'bid'   => $bid,
                    'eid'   => $eid,
                ]),
                'blogUrl'   => acmsLink([
                    'admin' => ADMIN,
                    'bid'   => $bid,
                ]),
                'userUrl'   => acmsLink([
                    'admin' => ADMIN,
                    'uid'   => $uid,
                ]),
                'editUrl'   => acmsLink([
                    'admin' => 'entry_editor',
                    'eid'   => $eid,
                ]),
            ];
            if (!empty($delUid)) {
                $_vars += [
                    'delUserName' => ACMS_RAM::userName($delUid),
                    'delUserIcon' => loadUserIcon($delUid),
                    'delUserUrl' => acmsLink([
                        'admin' => ADMIN,
                        'uid' => $delUid,
                    ]),
                ];
            }

            if ($cid) {
                $_vars += [
                    'categoryName' => ACMS_RAM::categoryName($cid),
                    'categoryUrl' => acmsLink([
                        'admin' => ADMIN,
                        'cid' => $cid,
                    ]),
                ];
            }

            //------------
            // sort#value
            if ('self' == $axis) {
                if ($UID) {
                    $sort   = $row['entry_user_sort'];
                } elseif (CID) {
                    $sort   = $row['entry_category_sort'];
                } else {
                    $sort   = $row['entry_sort'];
                }

                $_vars  += [
                    'sort'      => $sort,
                    'sort#eid'  => $eid,
                ];
            }

            //---------
            // delete
            do {
                if (config('approval_contributor_edit_auth') !== 'on' && enableApproval(BID, CID)) {
                    if (!sessionWithApprovalAdministrator(BID, CID)) {
                        break;
                    }
                } elseif (roleAvailableUser()) {
                    if (!roleAuthorization('entry_delete', BID, $eid)) {
                        break;
                    }
                }
                $Tpl->add(['adminDeleteActionLoop', 'entry:loop']);
            } while (false);

            //-------
            // field
            $_vars  += $this->buildField(loadEntryField($eid), $Tpl, 'entry:loop', 'entry');

            $Tpl->add('status#' . $row['entry_status']);
            $Tpl->add('entry:loop', $_vars);
        }

        do {
            if (config('approval_contributor_edit_auth') !== 'on' && enableApproval(BID, CID)) {
                if (!sessionWithApprovalAdministrator(BID, CID)) {
                    break;
                }
            } elseif (roleAvailableUser()) {
                if (!roleAuthorization('entry_delete', BID)) {
                    break;
                }
            }
            $Tpl->add(['adminDeleteAction']);
            $Tpl->add(['adminDeleteAction2']);
        } while (false);

        //-------------
        // sort:action
        if ('self' == $axis) {
            if ($UID) {
                $Tpl->add('sort:action#user');
            } elseif (CID) {
                $Tpl->add('sort:action#category');
            } else {
                $Tpl->add('sort:action#entry');
            }
        }

        //------------
        // userSelect
        if (sessionWithCompilation()) {
            $Tpl->add('userSelect#batch');
        }


        $Tpl->add(null, $vars);
        return $Tpl->get();
    }
}
