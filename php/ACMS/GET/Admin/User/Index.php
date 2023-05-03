<?php

class ACMS_GET_Admin_User_Index extends ACMS_GET_Admin
{

    var $_scope = array(
        'field'     => 'global'
    );

    function get()
    {
        if ( !sessionWithAdministration() ) return false;

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $vars   = array();

        //---------
        // refresh
        if ( !$this->Post->isNull() ) {
            //-------------------
            // user limit error（権限の一括変更時）
            if ( !$this->Post->isValid('user', 'limit') ) {
                $Tpl->add('user:validator#limit');
            } else {
                $Tpl->add('refresh');
            }
            $vars['notice_mess']    = 'show';
        }

        //----------
        // init SQL
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('user');
        $SQL->addWhereOpr('user_pass', '', '<>');

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

        //------
        // auth
        $auth   = $this->Get->get('auth');
        $vars['auth:selected#'.$auth]   = config('attr_selected');

        //--------
        // status
        $status = $this->Get->get('status');
        $vars['status:selected#'.$status]   = config('attr_selected');

        // keyword
        if ( !!KEYWORD ) {
            $SQL->addLeftJoin('fulltext', 'fulltext_uid', 'user_id');
            $keywords = preg_split(REGEX_SEPARATER, KEYWORD, -1, PREG_SPLIT_NO_EMPTY);
            foreach ( $keywords as $keyword ) {
                $SQL->addWhereOpr('fulltext_value', '%'.$keyword.'%', 'LIKE');
            }
        }

        //-------
        // order
        $order  = ORDER ? ORDER : 'sort-asc';
        $vars['order:selected#'.$order] = config('attr_selected');
        if ( 1
            && $order === 'sort-asc'
            && !KEYWORD
            && empty($status)
            && empty($auth)
            && $axis === 'self'
        ) {
            $vars['sortable'] = 'on';
        } else {
            $vars['sortable'] = 'off';
        }

        //--------------
        // switch user
        $canSwitchUser = config('switch_user_enable') === 'on';
        if (config('switch_user_permission') === 'root' && RBID != SBID) {
            $canSwitchUser = false;
        }

        //--------
        // limit
        $limits = configArray('admin_limit_option');
        $limit  = LIMIT ? LIMIT : $limits[config('admin_limit_default')];
        foreach ( $limits as $val ) {
            $_vars  = array('value' => $val);
            if ( $limit == $val ) $_vars['selected'] = config('attr_selected');
            $Tpl->add('limit:loop', $_vars);
        }

        $SQL->addLeftJoin('blog', 'blog_id', 'user_blog_id');
        ACMS_Filter::blogTree($SQL, $target_bid, $axis);
        ACMS_Filter::blogStatus($SQL);

        // field
        if (!empty($this->Field)) {
            ACMS_Filter::userField($SQL, $this->Field);
        }

        if ( !empty($auth) ) $SQL->addWhereOpr('user_auth', $auth);
        if ( !empty($status) ) $SQL->addWhereOpr('user_status', $status);

        $Amount = new SQL_Select($SQL);
        $Amount->setSelect('*', 'user_amount', null, 'count');
        if ( !$pageAmount = $DB->query($Amount->get(dsn()), 'one') ) {
            $Tpl->add('index#notFound');
            $Tpl->add(null, $vars);
            return $Tpl->get();
        }
        $vars   += $this->buildPager(PAGE, $limit, $pageAmount
            , config('admin_pager_delta'), config('admin_pager_cur_attr'), $Tpl, array(), array('admin' => ADMIN)
        );

        $SQL->addLeftJoin('entry', 'entry_user_id', 'user_id');
        $SQL->addSelect('user_id');
        $SQL->addSelect('user_sort');
        $SQL->addSelect('user_name');
        $SQL->addSelect('user_mail');
        $SQL->addSelect('user_code');
        $SQL->addSelect('user_auth');
        $SQL->addSelect('user_status');
        $SQL->addSelect('user_blog_id');
        $SQL->addSelect('entry_user_id', 'entry_amount', null, 'COUNT');

        $SQL->setGroup('user_id');
        $SQL->setLimit($limit, (PAGE - 1) * $limit);
        ACMS_Filter::userOrder($SQL, $order);

        $q  = $SQL->get(dsn());

        $DB->query($q, 'fetch');
        while ( $row = $DB->fetch($q) ) {
            $bid    = $row['user_blog_id'];
            $uid    = $row['user_id'];
            $auth   = getAuthConsideringRole($uid);
            $Tpl->add('auth#'.$auth);
            if (isRoleAvailableUser($uid)) {
                $Tpl->add('auth_default#' . $row['user_auth']);
            }
            $Tpl->add('status#'.$row['user_status']);

            $_vars  = array(
//                'bid'   => $bid,
                'uid'   => $uid,
                'name'  => $row['user_name'],
                'icon'  => loadUserIcon($uid),
                'mail'  => $row['user_mail'],
                'code'  => $row['user_code'],
                'amount'=> $row['entry_amount'],
                'itemUrl'   => acmsLink(array(
                    'admin' => 'user_edit',
                    'bid'   => $bid,
                    'uid'   => $uid,
                )),
            );

            //-------------
            // switch user
            if (1
                && $canSwitchUser
                && SUID != $uid
                && !(config('switch_user_same_level') !== 'on' && $row['user_auth'] === 'administrator')
                && canSwitchUser($uid)
            ) {
                $_vars['catSwitchUser'] = 'yes';
            }

            //------
            // sort
            if ( 'self' == $axis ) {
                $_vars  += array(
                    'sort'      => $row['user_sort'],
                    'sort#uid'  => $uid,
                );
            }

            //-------
            // field
            $_vars  += $this->buildField(loadUserField($uid), $Tpl, 'user:loop');

            $Tpl->add('user:loop', $_vars);
        }

        //--------------------
        // sort header, action
        if ( 'self' == $axis ) {
            $Tpl->add('sort#header');
            $Tpl->add('sort#action');
        }

        $Tpl->add(null, $vars);
        return $Tpl->get();
    }
}
