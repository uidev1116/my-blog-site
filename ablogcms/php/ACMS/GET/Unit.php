<?php

class ACMS_GET_Unit extends ACMS_GET_Entry
{
    function buildUnit(&$Column, &$Tpl, $rootBlock = array(), $preAlign = null, $renderGroup = true)
    {
        return $this->buildColumn($Column, $Tpl, $rootBlock, $preAlign, $renderGroup);
    }
}
