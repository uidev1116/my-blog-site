<?php

class ACMS_GET_Touch_Role_TagEdit extends ACMS_GET
{
    function get()
    {
        return ( roleAuthorization('tag_edit', BID) || !roleAvailableUser() ) ? $this->tpl : false;
    }
}
