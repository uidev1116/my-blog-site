<?php

class ACMS_POST_Shortcut_Index_Sort extends ACMS_POST
{
    function post()
    {
        if ( !$arySort = $this->Post->getArray('sort') ) {
            $this->Post->set('sort#null', true);
            return $this->Post;
        }

        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('dashboard');
        $SQL->setSelect('dashboard_key');
        $SQL->addWhereOpr('dashboard_key', 'shortcut_%', 'LIKE');
        $SQL->addWhereOpr('dashboard_blog_id', BID);
        $SQL->setOrder('dashboard_sort');
        if ( !$all = $DB->query($SQL->get(dsn()), 'all') ) {
            $this->Post->set('sort#shortcut_is_empty', true);
            return $this->Post;
        }

        $Data    = array();
        foreach ( $all as $row ) {
            if ( !preg_match('@^(.+_)([^_]+)$@', $row['dashboard_key'], $match) ) continue;
            $Data[$match[1]][]  = $match[2];
        }

        if ( count($Data) <> count($arySort) ) {
            $this->Post->set('sort#shortcut_is_invalid', true);
            return $this->Post;
        }

        $Key    = array();
        foreach ( array_keys($Data) as $i => $key ) {
            $Key[$key]  = $arySort[$i];
        }
        asort($Key);

        $i  = 1;
        foreach ( $Key as $fd => $kipple ) {
            foreach ( $Data[$fd] as $key ) {
                $SQL    = SQL::newUpdate('dashboard');
                $SQL->setUpdate('dashboard_sort', $i);
                $SQL->addWhereOpr('dashboard_key', $fd.$key);
                $SQL->addWhereOpr('dashboard_blog_id', BID);
                $DB->query($SQL->get(dsn()), 'exec');
                $i++;
            }
        }

        $this->Post->set('sort#success', true);
        $this->Post->set('notice_mess', 'show');
        
        return $this->Post;
    }
}
