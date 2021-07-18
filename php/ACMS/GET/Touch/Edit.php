<?php

class ACMS_GET_Touch_Edit extends ACMS_GET
{
    function get()
    {
        return ( 1
            and !!ADMIN
            and ( 0
                or 'entry-edit' == ADMIN
                or 'entry_editor' == ADMIN
                or 'entry-add' == substr(ADMIN, 0, 9)
            )
        ) ? $this->tpl : false;
    }
}
