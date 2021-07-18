<?php

class ACMS_GET_Touch_Role_PublishEdit extends ACMS_GET
{
    function get()
    {
        return ( roleAuthorization('publish_edit', BID) || !roleAvailableUser() ) ? $this->tpl : false;
    }
}
