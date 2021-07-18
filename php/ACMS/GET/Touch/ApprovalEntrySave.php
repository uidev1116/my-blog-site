<?php

class ACMS_GET_Touch_ApprovalEntrySave extends ACMS_GET
{
    function get()
    {
        $Session    =& Field::singleton('session');
        $action     = $Session->get('entry_action', '');
        $Session->delete('entry_action');

        return empty($action) ? false : $this->tpl;
    }
}