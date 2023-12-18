<?php

class ACMS_GET_Touch_Role_NotPublishEdit extends ACMS_GET
{
    function get()
    {
        return roleAuthorization('publish_edit', BID) ? false : $this->tpl;
    }
}
