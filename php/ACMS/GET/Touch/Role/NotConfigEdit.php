<?php

class ACMS_GET_Touch_Role_NotConfigEdit extends ACMS_GET
{
    function get()
    {
        return roleAuthorization('config_edit', BID) ? false : $this->tpl;
    }
}
