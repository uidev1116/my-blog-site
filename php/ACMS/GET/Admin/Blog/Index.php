<?php

class ACMS_GET_Admin_Blog_Index extends ACMS_GET_Admin
{
    function get()
    {
        if ( 'blog_index' <> ADMIN && 'blog_edit' <> ADMIN ) return '';
        if ( !sessionWithAdministration() ) return '';

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $vars   = array();

        if ( !$this->Post->isNull() ) {
            $Tpl->add('refresh');
            $vars['notice_mess'] = 'show';
        }

        //-------
        // order
        $order  = ORDER ? ORDER : 'sort-asc';

        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('blog');
        $SQL->addLeftJoin('config_set', 'config_set_id', 'blog_config_set_id');
        $SQL->addWhereOpr('blog_parent', BID);

        $Pager  = new SQL_Select($SQL);
        $Pager->setSelect('*', 'blog_amount', null, 'COUNT');
        if ( !$amount = $DB->query($Pager->get(dsn()), 'one') ) {
            $Tpl->add('index#notFound');
            $vars['notice_mess'] = 'show';
            $Tpl->add(null, $vars);
            return $Tpl->get();
        }

        $vars['order:selected#'.$order] = config('attr_selected');
        if ( $order === 'sort-asc' || $order === 'sort-desc' ) {
            $vars['sortable'] = 'on';
        } else {
            $vars['sortable'] = 'off';
        }

        //-------
        // limit
        $limits = configArray('admin_limit_option');
        $limit  = LIMIT ? LIMIT : $limits[config('admin_limit_default')];
        $from   = (PAGE - 1) * $limit;

        $SQL->setLimit($limit, $from);

        foreach ( $limits as $val ) {
            $_vars  = array(
                'value' => $val,
                'label' => $val,
            );
            if ( $limit == $val ) $_vars['selected'] = config('attr_selected');
            $Tpl->add('limit:loop', $_vars);
        }

        $vars   += $this->buildPager(PAGE, $limit, $amount,
            config('admin_pager_delta'), config('admin_pager_cur_attr'), $Tpl, array(),
            array('admin' => ADMIN)
        );

        ACMS_Filter::blogOrder($SQL, $order);

        $q  = $SQL->get(dsn());
        $DB->query($q, 'fetch');

        while ( $row = $DB->fetch($q) ) {
            $bid    = $row['blog_id'];
            $Tpl->add('status#'.$row['blog_status']);
            $_vars  = array(
                'bid'       => $bid,
                'sort'      => $row['blog_sort'],
                'name'      => $row['blog_name'],
                'configSet' => $row['config_set_name'],
                'urlValue'  => acmsLink(array('bid' => $bid)),
                'urlLabel'  => acmsLink(array('bid' => $bid, 'sid' => false)),
                'adminTopLink'  => acmsLink(array(
                    'bid'   => $bid,
                    'admin' => 'top',
                )),
                'itemLink'  => acmsLink(array(
                    'bid'   => $bid,
                    'admin' => 'blog_edit',
                )),
            );
            if ( isBlogGlobal($bid) ) {
                $_vars['indexLink'] = acmsLink(array(
                    'bid'   => $bid,
                    'admin' => 'blog_index',
                ));
                $Tpl->add(array('branch', 'blog:loop'));
            }
            $Tpl->add('blog:loop', $_vars);
        }

        //---------
        // success
        if ( $success = $this->Post->get('success') ) {
            $vars['success'] = $success;
        }

        //--------
        // error
        if ( $error = $this->Post->get('error') ) {
            $vars['error'] = $error;
        }

        $Tpl->add(null, $vars);
        return $Tpl->get();
    }
}
