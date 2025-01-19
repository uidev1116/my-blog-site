<?php

namespace Acms\Services\Unit\Contracts;

use Template;
use Field;

interface UnitSetting
{
    /**
     * ユニット設定の専用コンフィグ設定を描画
     *
     * @param Template $tpl
     * @param Field $config
     * @return void
     */
    public function renderUnitSettings(Template $tpl, Field $config): void;
}
