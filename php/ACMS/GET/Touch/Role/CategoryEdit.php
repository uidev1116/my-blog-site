<?php

class ACMS_GET_Touch_Role_CategoryEdit extends ACMS_GET
{
    function get()
    {
        return ( roleAuthorization('category_edit', BID) || !roleAvailableUser() ) ? $this->tpl : false;
    }
}
