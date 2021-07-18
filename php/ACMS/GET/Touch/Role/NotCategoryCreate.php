<?php

class ACMS_GET_Touch_Role_NotCategoryCreate extends ACMS_GET
{
    function get()
    {
        return roleAuthorization('category_create', BID) ? false : $this->tpl;
    }
}
