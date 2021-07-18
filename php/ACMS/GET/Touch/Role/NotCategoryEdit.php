<?php

class ACMS_GET_Touch_Role_NotCategoryEdit extends ACMS_GET
{
    function get()
    {
        return roleAuthorization('category_edit', BID) ? false : $this->tpl;
    }
}
