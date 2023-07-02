<?php

class ACMS_GET_Admin_Shortcut_List extends ACMS_GET_Admin
{
    function get()
    {
        if ( !sessionWithContribution() ) return '';

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('dashboard');
        $SQL->addWhereOpr('dashboard_key', 'shortcut_%', 'LIKE');
        $SQL->addWhereOpr('dashboard_blog_id', BID);
        $SQL->setOrder('dashboard_sort');
        if ( !$all = $DB->query($SQL->get(dsn()), 'all') ) return '';

        $Tmp    = array();
        foreach ( $all as $row ) {
            if ( !preg_match('@^shortcut_((?:(?:bid|uid|cid|eid|rid|mid|fmid|mbid|scid|null)_(?:\d+|null)_)*)(.+)_([^_]+)$@', $row['dashboard_key'], $match) ) continue;
            $key = $match[1].$match[2];
            $action = $match[3];
            $Tmp[$key]['query'] = $match[1];
            $Tmp[$key]['admin'] = $match[2];
            $Tmp[$key][$action] = $row['dashboard_value'];
        }

        // auth
        $aryAuth    = array();
        if ( sessionWithContribution() ) $aryAuth[] = 'contribution';
        if ( sessionWithCompilation() ) $aryAuth[]  = 'compilation';
        if ( sessionWithAdministration() ) $aryAuth[]   = 'administration';

        $_Tmp   = $Tmp;
        $Tmp    = array();
        foreach ( $_Tmp as $hash => $data ) {
            if ( !in_array($data['auth'], $aryAuth) ) continue;
            $Tmp[$hash] = $data;
        }
        if ( empty($Tmp) ) return '';

        foreach ( $Tmp as $hash => $data ) {
            $query = $data['query'];
            $admin = $data['admin'];

            $ids = $this->extractQuery($query);

            $Tpl->add('shortcut:loop', array(
                'admin' => $admin,
                'name'  => $data['name'],
                'url'   => $this->createUrl($admin, $ids),
            ));
        }
        return $Tpl->get();

    }
}
