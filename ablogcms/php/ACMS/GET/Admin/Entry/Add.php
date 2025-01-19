<?php

use Acms\Services\Facades\Application;
use Acms\Traits\Unit\UnitRepositoryTrait;

class ACMS_GET_Admin_Entry_Add extends ACMS_GET_Admin_Entry
{
    use UnitRepositoryTrait;

    public function get()
    {
        if ('entry-add' !== substr(ADMIN, 0, 9)) {
            return '';
        }
        if (!sessionWithContribution()) {
            return '';
        }
        $addType = substr(ADMIN, 10);

        /** @var \Acms\Services\Unit\Repository $unitService */
        $unitService = Application::make('unit-repository');
        /** @var \Acms\Services\Unit\Rendering\Edit $unitRenderingService */
        $unitRenderingService = Application::make('unit-rendering-edit');

        $units = $unitService->loadAddUnit($addType);
        $offset = EID ? $this->countUnitsTrait(EID) : 0; // @phpstan-ignore-line
        if ($this->Get->get('limit')) {
            $offset = (int) $this->Get->get('limit');
        }
        $tpl = new Template($this->tpl, new ACMS_Corrector());
        $unitRenderingService->renderAddUnit($units, $offset, $tpl, []);

        return $tpl->get();
    }
}
