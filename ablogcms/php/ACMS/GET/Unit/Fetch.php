<?php

use Acms\Services\Facades\Application;

class ACMS_GET_Unit_Fetch extends ACMS_GET_Unit
{
    public function get()
    {
        $Tpl = new Template($this->tpl, new ACMS_Corrector());

        $utid = (int) $this->Post->get('utid', UTID);
        $ary_utid = array_map('intval', $this->Post->getArray('utid'));
        $eid = (int) $this->Post->get('eid', EID);
        $renderGroup = $this->Post->get('renderGroup', 'off');
        $renderGroup = ($renderGroup === 'on') ? true : false;

        $seeked = false;
        $preAlign = null;

        /** @var \Acms\Services\Unit\Repository $unitRepository */
        $unitRepository = Application::make('unit-repository');
        /** @var \Acms\Services\Unit\Rendering\Front $unitRenderingService */
        $unitRenderingService = Application::make('unit-rendering-front');

        // if Add
        if (empty($utid)) {
            $sort = (int) $this->Get->get('sort');
            $unit = $unitRepository->getUnitBySortTrait($eid, $sort);
            $utid = (int) ($unit['column_id'] ?? 0);
        }
        if ($units = $unitRepository->loadUnits($eid)) {
            foreach ($units as $i => $row) {
                if ($seeked !== false) {
                    $preAlign = $row->getAlign();
                    $seeked = false;
                }
                if (is_array($ary_utid) && (count($ary_utid) > 0)) {
                    if (in_array($row->getId(), $ary_utid, true) === false) {
                        unset($units[$i]);
                    } else {
                        $seeked = true;
                    }
                } else {
                    if ($row->getId() !== $utid) {
                        unset($units[$i]);
                    } else {
                        $seeked = true;
                    }
                }
            }
            $units = array_reverse($units);
            $unitRenderingService->render($units, $Tpl, $eid, $preAlign, $renderGroup);
        }
        return $Tpl->get();
    }
}
