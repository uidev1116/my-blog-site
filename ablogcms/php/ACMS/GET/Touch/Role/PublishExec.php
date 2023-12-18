<?php

class ACMS_GET_Touch_Role_PublishExec extends ACMS_GET
{
    function get()
    {
        return ( roleAuthorization('publish_exec', BID) || !roleAvailableUser() ) ? $this->tpl : false;
    }
}
