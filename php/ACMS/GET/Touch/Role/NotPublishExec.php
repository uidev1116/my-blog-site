<?php

class ACMS_GET_Touch_Role_NotPublishExec extends ACMS_GET
{
    function get()
    {
        return roleAuthorization('publish_exec', BID) ? false : $this->tpl;
    }
}
