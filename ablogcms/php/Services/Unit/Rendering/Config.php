<?php

namespace Acms\Services\Unit\Rendering;

use Acms\Traits\Unit\UnitTemplateTrait;
use Acms\Services\Facades\Template as TemplateHelper;
use Acms\Services\Facades\Application;
use Template;
use Field;

class Config
{
    use UnitTemplateTrait;

    /**
     * ユニット設定を描画
     *
     * @param string $pfx
     * @param string $type
     * @param Template $tpl
     * @param Field $config
     * @return void
     */
    public function render(string $pfx, string $type, Template $tpl, Field $config): void
    {
        $unitField = new Field();
        $unitField->setField('pfx', $pfx);
        $baseType = detectUnitTypeSpecifier($type);

        /** @var \Acms\Services\Unit\Repository $unitRepository */
        $unitRepository = Application::make('unit-repository');
        $unitModel = $unitRepository->makeModel($type);
        if (empty($unitModel)) {
            return;
        }
        if ($unitModel instanceof \Acms\Services\Unit\Contracts\UnitSetting) {
            $unitModel->renderUnitSettings($tpl, $config);
        }
        foreach ($config->getArray("column_{$baseType}_size") as $j => $size) {
            $tpl->add(['size:loop', $type], [
                'value' => $size,
                'label' => $config->get("column_{$baseType}_size_label", '', $j),
            ]);
        }
        if ($config->get('unit_group') === 'on' && !preg_match('/^(break|module|custom)$/', $baseType)) {
            $classes = $config->getArray('unit_group_class');
            $labels  = $config->getArray('unit_group_label');
            foreach ($labels as $i => $label) {
                $tpl->add(['group:loop', 'group:veil', $baseType], [
                    'group.value' => $classes[$i],
                    'group.label' => $label,
                    'group.selected' => ($classes[$i] === $config->get('group')) ? $config->get('attr_selected') : '',
                ]);
            }
            $tpl->add(['group:veil', $type], [
                'group.pfx' => $unitField->get('pfx'),
            ]);
        }
        $vars = TemplateHelper::buildField($unitField, $tpl, $baseType, 'column');
        $vars += [
            'actualType'  => $type,
            'actualLabel' => $this->getUnitLabelTrait($type),
        ];
        $tpl->add($baseType, $vars);
    }
}
