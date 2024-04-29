<?php

/**
 * LEGACY CLASS
 *
 * @old ACMS_GET_Column_List
 * @new ACMS_GET_Unit_List
 */
class ACMS_GET_Column_List extends ACMS_GET_Unit_List
{
    public $_axis = [
        'bid'   => 'descendant-or-self',
        'cid'   => 'descendant-or-self',
    ];

    public $_scope = [
        'cid'       => 'global',
        'eid'       => 'global',
        'start'     => 'global',
        'end'       => 'global',
    ];

    function get()
    {
        return parent::get();
    }
}
