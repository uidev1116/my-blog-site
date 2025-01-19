<?php

use Acms\Services\Facades\Application;

class ACMS_GET_Admin_Unit_Single extends ACMS_GET_Admin_Entry
{
    public function get()
    {
        if ('entry-update-unit' <> substr(ADMIN, 0, 17)) {
            return '';
        }
        if (!sessionWithContribution()) {
            return '';
        }
        $addType = substr(ADMIN, 18); // URLからユニットタイプを取得
        $tpl = new Template($this->tpl, new ACMS_Corrector());
        $unit = null;

        /** @var \Acms\Services\Unit\Repository $unitService */
        $unitService = Application::make('unit-repository');
        /** @var \Acms\Services\Unit\Rendering\Edit $unitRenderingService */
        $unitRenderingService = Application::make('unit-rendering-edit');

        if ($addType) {
            // add
            if ($unit = $unitService->create($addType, $addType, 0)) {
                $sort = UTID ? (int) ACMS_RAM::unitSort(UTID) : 0; // @phpstan-ignore-line
                if ($this->Get->get('pos', 'below') === 'below') {
                    $unit->setSort($sort + 1);
                } else {
                    $unit->setSort($sort);
                }
                if ($unit->getUnitType() === 'media') {
                    $unit->setField2('');
                }
            }
        } elseif (defined('UTID') && UTID) { // @phpstan-ignore-line
            // update
            if ($unit = $unitService->loadUnit(UTID)) {
                $unit->setTempId(UTID);
            }
        }
        if (is_null($unit)) {
            httpStatusCode('404 Not Found');
            return '';
        }
        $unitRenderingService->renderAddUnitDirect($unit, $tpl, ACMS_RAM::entryPrimaryImage(EID), []);

        return $tpl->get();
    }
}
