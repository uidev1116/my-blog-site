<?php

class ACMS_GET_Admin_Shortcut_Edit extends ACMS_GET_Admin_Edit
{
    /**
     * @return bool
     */
    function validate()
    {
        if ( 'shortcut_edit' <> ADMIN ) { return false; }
        if ( !$this->Get->get('admin') ) { return false; }
        if ( !$this->Get->get('action') ) { return false; }

        return true;
    }

    /**
     * @param string $admin
     * @param array $ids
     *
     * @return string
     */
    function getShortcutKey($admin, $ids)
    {
        $str = 'shortcut_';

        foreach ( $ids as $key => $id ) {
            $str .= ($key . '_' . $id .'_');
        }
        $str .= ($admin . '_');

        return $str;
    }

    function edit(& $Tpl)
    {
        if ( !$this->validate() ) {
            return false;
        }
        $admin  = $this->Get->get('admin');
        $ids = array();

        foreach ( array('bid', 'uid', 'cid', 'eid', 'rid', 'mid', 'fmid', 'mbid', 'scid') as $idKey ) {
            $id = $this->Get->get($idKey);
            if ( empty($id) or is_bool($id) ) continue;
            $ids[$idKey] = intval($id);
        }
        $id_str = $this->getShortcutKey($admin, $ids);
        $Shortcut =& $this->Post->getChild('shortcut');

        if ( 'add' == $this->edit ) {
            $DB     = DB::singleton(dsn());
            $SQL    = SQL::newSelect('dashboard');
            $SQL->setSelect('dashboard_key');
            $SQL->addWhereOpr('dashboard_key', $id_str . '%', 'LIKE');
            $SQL->addWhereOpr('dashboard_blog_id', BID);
            $SQL->setLimit(1);
            if ( !!$DB->query($SQL->get(dsn()), 'one') ) {
                foreach ( array('action', 'auth', 'name') as $key ) {
                    $SQL    = SQL::newSelect('dashboard');
                    $SQL->setSelect('dashboard_value');
                    $SQL->addWhereOpr('dashboard_key', $id_str . $key);
                    $SQL->addWhereOpr('dashboard_blog_id', BID);
                    $Shortcut->set($key, $DB->query($SQL->get(dsn()), 'one'));
                }
                if ( $Shortcut->isNull() ) return false;
                $this->edit = 'update';
            } else {
                $this->edit = 'insert';
            }
        } else if ( $this->edit !== 'delete' ) {
            $DB     = DB::singleton(dsn());
            foreach ( array('action', 'auth', 'name') as $key ) {
                $SQL    = SQL::newSelect('dashboard');
                $SQL->setSelect('dashboard_value');
                $SQL->addWhereOpr('dashboard_key', $id_str . $key);
                $SQL->addWhereOpr('dashboard_blog_id', BID);
                $Shortcut->set($key, $DB->query($SQL->get(dsn()), 'one'));
            }
            if ( $Shortcut->isNull() ) return false;
        }

        $Shortcut->set('url', $this->createUrl($admin, $ids));

        return true;
    }
}
