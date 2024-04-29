<?php

class ACMS_GET_Touch_Edit extends ACMS_GET
{
    public function get()
    {
        return ( 1
            && !!ADMIN
            && ( 0
                || 'entry-edit' === ADMIN
                || 'entry_editor' === ADMIN
                || 'entry-add' === substr(ADMIN, 0, 9)
            )
        ) ? $this->tpl : '';
    }
}
