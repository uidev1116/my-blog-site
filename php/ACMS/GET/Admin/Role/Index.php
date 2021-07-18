<?php

class ACMS_GET_Admin_Role_Index extends ACMS_GET_Admin
{
    function get()
    {
        if ( BID !== 1 || !sessionWithEnterpriseAdministration() ) { return ''; }

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $order  = ORDER ? ORDER : 'id-asc';
        $vars   = array();
        $vars['order:selected#'.$order] = config('attr_selected');
        list($field, $order) = explode('-', $order);

        //---------
        // refresh
        if ( !$this->Post->isNull() ) {
            $Tpl->add('refresh');
            $vars['notice_mess'] = 'show';
        }

        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('role');
        $SQL->setOrder('role_'.$field, $order);

        $q  = $SQL->get(dsn());
        if ( !$DB->query($q, 'fetch') or !($row = $DB->fetch($q)) ) {
            $Tpl->add('index#notFound');
            $vars['notice_mess'] = 'show';
        }

        $all    = $DB->query($q, 'all');
        foreach ( $all as $i => $row ) {
            $rid    = intval($row['role_id']);
            $var    = array(
                'name'          => $row['role_name'],
                'description'   => $row['role_description'],
                'rid'           => $row['role_id'],
            );

            // blog count
            $SQL    = SQL::newSelect('role_blog');
            $SQL->addSelect('blog_id', null, null, 'COUNT');
            $SQL->addWhereOpr('role_id', $rid);
            if ( $blog_amount = $DB->query($SQL->get(dsn()), 'one') ) {
                $var['blog_amount'] = $blog_amount;
            }

            if ( !empty($rid) ) {
                $var['itemUrl'] = acmsLink(array(
                    'bid'   => 1,
                    'admin' => 'role_edit',
                    'query' => array(
                        'rid'   => $rid,
                    ),
                ));
            }
            $Tpl->add('role:loop', $var);
        }
        $Tpl->add(null, $vars);

        return $Tpl->get();
    }
}

