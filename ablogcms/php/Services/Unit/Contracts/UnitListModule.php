<?php

namespace Acms\Services\Unit\Contracts;

use Template;

interface UnitListModule
{
    /**
     * Unit_Listモジュールを描画
     *
     * @param Template $tpl
     * @return array
     */
    public function renderUnitListModule(Template $tpl): array;
}
