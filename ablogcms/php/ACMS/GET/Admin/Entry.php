<?php

class ACMS_GET_Admin_Entry extends ACMS_GET_Admin
{
    function buildFormColumn($data, &$Tpl, $rootBlock = [])
    {
        return Tpl::buildAdminFormColumn($data, $Tpl, $rootBlock);
    }
}
