<?php

class ACMS_GET_Touch_Role_CategoryCreate extends ACMS_GET
{
    function get()
    {
        return ( roleAuthorization('category_create', BID) || !roleAvailableUser() ) ? $this->tpl : false;
    }
}
