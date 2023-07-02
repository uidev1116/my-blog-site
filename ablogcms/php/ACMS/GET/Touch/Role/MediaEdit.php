<?php

class ACMS_GET_Touch_Role_MediaEdit extends ACMS_GET
{
    function get()
    {
        return ( roleAuthorization('media_edit', BID) || !roleAvailableUser() ) ? $this->tpl : false;
    }
}
