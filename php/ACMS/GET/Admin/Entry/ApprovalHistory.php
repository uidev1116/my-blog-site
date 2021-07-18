<?php

class ACMS_GET_Admin_Entry_ApprovalHistory extends ACMS_GET_Admin_Entry
{
    function get()
    {
        if ( 'entry_approval-history' <> ADMIN ) return '';
        if ( !enableApproval() ) return '';
        if ( !EID ) return '';
        if ( !sessionWithApprovalAdministrator() ) return '';

        $order  = ORDER ? ORDER : 'desc';
        $limits = configArray('admin_limit_option');
        $limit  = LIMIT ? LIMIT : $limits[config('admin_limit_default')];

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $vars   = array();

        //----------
        // init SQL
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('approval');
        $SQL->addWhereOpr('approval_entry_id', EID);

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
        $SQL->setOrder('approval_datetime', $order);

        $q  = $SQL->get(dsn());
        $DB->query($q, 'fetch');

        while ( $row = $DB->fetch($q) ) {
            $_vars  = array();
            $rvid   = $row['approval_revision_id'];
            $type   = $row['approval_type'];

            $REV    = SQL::newSelect('entry_rev');
            $REV->addSelect('entry_rev_memo');
            $REV->addSelect('entry_status');
            $REV->addWhereOpr('entry_id', EID);
            $REV->addWhereOpr('entry_rev_id', $rvid);
            if ( !!$rev = $DB->query($REV->get(dsn()), 'row') ) {
                $_vars['title'] = $rev['entry_rev_memo'];
                $_type          = $rev['entry_status'];
                if ( $_type === 'trash' && $type === 'request' ) {
                    $type = $_type;
                }
            }

            $_vars   += array(
                'eid'               => EID,
                'rvid'              => $rvid,
                'type'              => $type,
                'datetime'          => $row['approval_datetime'],
                'requestUser'       => ACMS_RAM::userName($row['approval_request_user_id']),
                'requestUserIcon'   => loadUserIcon($row['approval_request_user_id']),
                'comment'           => $row['approval_comment'],
                'revisionUrl'       => acmsLink(array(
                    'bid'   => BID,
                    'eid'   => EID,
                    'tpl'   => 'ajax/revision-preview.html',
                    'query' => array(
                        'rvid'  => $rvid,
                        'trash' => 'show',
                    ),
                )),
            );
            $type = 'type#'.$type;
            $Tpl->add(array($type, 'history:loop'));
            $Tpl->add('history:loop', $_vars);
        }

        $Tpl->add(null, $vars);
        return $Tpl->get();
    }
}
