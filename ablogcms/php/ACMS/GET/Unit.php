<?php

class ACMS_GET_Unit extends ACMS_GET_Entry
{
    /**
     * @param array<array<string, mixed>> &$Column
     * @param Template &$Tpl
     * @param int $eid
     * @param string|null $preAlign
     * @param bool $renderGroup
     * @return true
     */
    public function buildUnit(&$Column, &$Tpl, $eid, $preAlign = null, $renderGroup = true)
    {
        return $this->buildColumn($Column, $Tpl, $eid, $preAlign, $renderGroup);
    }
}
