<?php

class ACMS_GET_Touch_Role_NotMediaEdit extends ACMS_GET
{
    function get()
    {
        return roleAuthorization('media_edit', BID) ? false : $this->tpl;
    }
}
