<?php

class ACMS_GET_Touch_EditUpdate extends ACMS_GET
{
    function get()
    {
        return ( 1
            and !!EID
            and !!ADMIN
            and !RVID
            and ( 0
                or 'entry-edit' == ADMIN
                or 'entry-add' == substr(ADMIN, 0, 9)
            )
        ) ? $this->tpl : false;
    }
}
