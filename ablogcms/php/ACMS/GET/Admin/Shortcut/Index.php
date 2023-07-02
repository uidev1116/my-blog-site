<?php

class ACMS_GET_Admin_Shortcut_Index extends ACMS_GET_Admin
{
    function buildTpl(& $Tpl, $Tmp)
    {
        $amount = count($Tmp);
        $i      = 1;
        foreach ( $Tmp as $hash => $data ) {
            $name = $data['name'];
            $auth = $data['auth'];
            $action = $data['action'];
            $query = $data['query'];
            $admin = $data['admin'];

            // sort
            for ( $j=1; $j<=$amount; $j++) {
                $_vars  = array(
                    'value' => $j,
                    'label' => $j,
                );
                if ( $i === $j ) $_vars['selected'] = config('attr_selected');
                $Tpl->add(array('sort:loop', 'shortcut:loop'), $_vars);
            }

            // auth
            $Tpl->add(array('auth#'.$auth, 'shortcut:loop'));

            // data
            $ids = $this->extractQuery($query);
            $Tpl->add('shortcut:loop', array(
                'name'  => $name,
                'url'   => $this->createUrl($admin, $ids),
                'itemUrl'   => acmsLink(array(
                    'bid'   => BID,
                    'admin' => 'shortcut_edit',
                    'query' => array_merge($ids, array(
                        'action' => $action,
                        'admin' => $admin,
                    )),
                )),
            ));
            $i++;
        }
        $Tpl->add(null, $this->buildField($this->Post, $Tpl));
    }

    function get()
    {
        if ( !sessionWithAdministration() ) return '';

        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('dashboard');
        $SQL->addWhereOpr('dashboard_key', 'shortcut_%', 'LIKE');
        $SQL->addWhereOpr('dashboard_blog_id', BID);
        $SQL->setOrder('dashboard_sort');
        if ( !$all = $DB->query($SQL->get(dsn()), 'all') ) {
            $Tpl->add('index#notFound');
            $Tpl->add(null, array('notice_mess' => 'show'));
            return $Tpl->get();
        }

        $Tmp = array();
        foreach ( $all as $row ) {
            if ( !preg_match('@^shortcut_((?:(?:bid|uid|cid|eid|rid|mid|fmid|mbid|scid|null)_(?:\d+|null)_)*)(.+)_([^_]+)$@', $row['dashboard_key'], $match) ) continue;
            $key = $match[1].$match[2];
            $action = $match[3];
            $Tmp[$key]['query'] = $match[1];
            $Tmp[$key]['admin'] = $match[2];
            $Tmp[$key][$action] = $row['dashboard_value'];
        }

        $this->buildTpl($Tpl, $Tmp);

        return $Tpl->get();

    }
}
