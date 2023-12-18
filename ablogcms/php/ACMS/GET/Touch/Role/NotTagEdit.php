<?php

class ACMS_GET_Touch_Role_NotTagEdit extends ACMS_GET
{
    function get()
    {
        return roleAuthorization('tag_edit', BID) ? false : $this->tpl;
    }
}
