<?php

class ACMS_GET_Admin_Comment_Index extends ACMS_GET_Admin
{
    function get()
    {
        if ( 'comment_index' <> ADMIN ) return false;
        if ( !sessionWithCompilation() ) return false;

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $Vars   = array();

        //---------
        // refresh
        if ( !$this->Post->isNull() ) {
            $Vars['notice_mess'] = 'show';
            $Tpl->add('refresh');
        }

        //------
        // axis
        $axis   = $this->Get->get('axis', 'descendant-or-self');
        if ( 1 < ACMS_RAM::blogRight(BID) - ACMS_RAM::blogLeft(BID) ) {
            $Tpl->add('axis', array(
                'axis:checked#'.$axis => config('attr_checked')
            ));
        } else {
            $axis   = 'self';
        }

        //--------
        // status
        $status = $this->Get->get('status');
        $Vars['status:selected#'.$status] = config('attr_selected');

        //-------
        // order
        $order  = $this->Q->get('order', 'datetime-desc');
        $Vars['order:selected#'.$order] = config('attr_selected');

        //--------
        // limit
        $aryLimit   = configArray('admin_limit_option');
        $limit      = $this->Q->get('limit', $aryLimit[config('admin_limit_default')]);
        foreach ( $aryLimit as $val ) {
            $_vars  = array('value' => $val);
            if ( $limit == $val ) $_vars['selected'] = config('attr_selected');
            $Tpl->add('limit:loop', $_vars);
        }

        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('comment');

        $SQL->addLeftJoin('blog', 'blog_id', 'comment_blog_id');
        ACMS_Filter::blogTree($SQL, BID, $axis);
        ACMS_Filter::blogStatus($SQL);

        //--------
        // status
        if ( in_array($status, array(
            'open', 'close', 'awaiting'
        )) ) $SQL->addWhereOpr('comment_status', $status);

        //-------
        // pager
        $Amount = new SQL_Select($SQL);
        $Amount->setSelect('*', 'comment_amount', null, 'count');
        if ( !$pageAmount = $DB->query($Amount->get(dsn()), 'one') ) {
            $Vars['notice_mess'] = 'show';
            $Tpl->add('index#notFound');
            $Tpl->add(null, $Vars);
            return $Tpl->get();
        }
        $Vars   += $this->buildPager(PAGE, $limit, $pageAmount
            , config('admin_pager_delta'), config('admin_pager_cur_attr'), $Tpl, array()
            , array('admin' => ADMIN,)
        );

        //-------
        // order
        $SQL->setOrder('comment_datetime', strpos($order, 'asc') ? 'ASC' : 'DESC');

        $SQL->setLimit($limit, (PAGE - 1) * $limit);

        $q  = $SQL->get(dsn());
        $DB->query($q, 'fetch');
        while ( $row = $DB->fetch($q) ) {
            $bid    = intval($row['comment_blog_id']);
            $eid    = intval($row['comment_entry_id']);
            $cmid   = intval($row['comment_id']);

            $Tpl->add('status#'.$row['comment_status']);

            if ( $url = $row['comment_url'] ) {
                $Tpl->add('url', $url);
                $Tpl->add('url#rear');
            }

            $vars   = array(
                'id'        => $cmid,
                'title'     => $row['comment_title'],
                'body'      => $row['comment_body'],
                'datetime'  => $row['comment_datetime'],
                'name'      => $row['comment_name'],
                'mail'      => $row['comment_mail'],
                'reftitle'  => ACMS_RAM::entryTitle($eid),
                'reflink'   => acmsLink(array(
                    'bid'   => $bid,
                    'eid'   => $eid,
                )),
                'blogName'  => ACMS_RAM::blogName($bid),
                'blogLink'  => acmsLink(array(
                    'bid'   => $bid,
                    'admin' => 'comment_index',
                )),
                'itemLink'  => acmsLink(array(
                    'bid'   => $bid,
                    'eid'   => $eid,
                    'cmid'  => $cmid,
                    'fragment'  => 'comment-'.$cmid,
                )),
            );
            if ( BID <> $bid ) $vars['disabled']   = config('attr_disabled');

            $Tpl->add('comment:loop', $vars);
        }

        $Tpl->add(null, $Vars);
        return $Tpl->get();
    }
}
