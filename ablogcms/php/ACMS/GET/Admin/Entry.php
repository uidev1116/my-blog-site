<?php

class ACMS_GET_Admin_Entry extends ACMS_GET_Admin
{
    function getColumnDefinition($mode, $type, $i)
    {
        return Tpl::getAdminColumnDefinition($mode, $type, $i);
    }

    function buildColumn($data, & $Tpl, $rootBlock=array(), $mediaData = array())
    {
        return Tpl::buildAdminColumn($data, $Tpl, $rootBlock, $mediaData);
    }

    function buildFormColumn($data, & $Tpl, $rootBlock=array())
    {
        return Tpl::buildAdminFormColumn($data, $Tpl, $rootBlock);
    }
}
