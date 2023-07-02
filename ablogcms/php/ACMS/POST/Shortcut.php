<?php

class ACMS_POST_Shortcut extends ACMS_POST
{
    var $isCacheDelete  = false;

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

    function post()
    {
        $admin  = $this->Get->get('admin');
        $action = $this->Get->get('action');
        $ids = array();

        foreach ( array('bid', 'uid', 'cid', 'eid', 'rid', 'mid', 'fmid', 'mbid', 'scid') as $idKey ) {
            $id = $this->Get->get($idKey);
            if ( empty($id) or is_bool($id) ) continue;
            $ids[$idKey] = intval($id);
        }
        $id_str = $this->getShortcutKey($admin, $ids);

        $Shortcut = $this->extract('shortcut');
        $Shortcut->setMethod('name', 'required');
        $Shortcut->setMethod('auth', 'required');
        $Shortcut->setMethod('shortcut', 'operative', sessionWithAdministration());
        $Shortcut->setMethod('shortcut', 'adminIsNull', !!$admin);
        $Shortcut->setMethod('shortcut', 'actionIsNull', !!$action);
        $Shortcut->validate(new ACMS_Validator());

        if ( $this->Post->isValidAll() ) {
            $data = array(
                $id_str . 'name' => $Shortcut->get('name'),
                $id_str . 'auth' => $Shortcut->get('auth'),
                $id_str . 'action' => $action,
            );
            $this->store($data);
        }
        return $this->Post;
    }

    function store($data=array())
    {
    }
}
