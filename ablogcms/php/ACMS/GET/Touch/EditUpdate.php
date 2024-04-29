<?php

class ACMS_GET_Touch_EditUpdate extends ACMS_GET
{
    public function get()
    {
        return ( 1
            && !!EID
            && !!ADMIN
            && !RVID
            && ( 0
                || 'entry-edit' === ADMIN
                || 'entry_editor' === ADMIN
                || 'entry-add' === substr(ADMIN, 0, 9)
            )
        ) ? $this->tpl : '';
    }
}
