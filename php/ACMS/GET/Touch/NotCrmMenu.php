<?php

class ACMS_GET_Touch_NotCrmMenu extends ACMS_GET_Touch_CrmMenu
{
    function get()
    {
        return in_array(ADMIN, $this->crm_admin_path) ? false : $this->tpl;
    }
}
