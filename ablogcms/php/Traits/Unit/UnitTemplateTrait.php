<?php

namespace Acms\Traits\Unit;

use Template;

trait UnitTemplateTrait
{
    /**
     * ユニットのラベルを取得
     *
     * @param string $type
     * @return string
     */
    protected function getUnitLabelTrait(string $type): string
    {
        $aryTypeLabel = [];
        foreach (configArray('column_add_type') as $i => $key) {
            $aryTypeLabel[$key] = config('column_add_type_label', '', $i);
        }
        return $aryTypeLabel[$type] ?? '';
    }

    /**
     * ユニット編集のサイズ選択肢を描画
     *
     * @param string $configType
     * @param string $templateType
     * @param string $size
     * @param Template $tpl
     * @param string[] $rootBlock
     * @return bool
     */
    protected function renderSizeSelectTrait(string $configType, string $templateType, string $size, Template $tpl, array $rootBlock = []): bool
    {
        $index = array_keys(configArray("column_{$configType}_size_label"));
        $matched = false;
        foreach ($index as $i) {
            $sizeVars  = [
                'value' => config("column_{$configType}_size", '', $i),
                'label' => config("column_{$configType}_size_label", '', $i),
                'display' => config("column_{$configType}_display_size", '', $i),
            ];
            if ($size === config("column_{$configType}_size", '', $i)) {
                $sizeVars['selected'] = config('attr_selected');
                $matched = true;
            }
            $tpl->add(array_merge(['size:loop', $templateType], $rootBlock), $sizeVars);
        }
        return $matched;
    }

    /**
     * ユニット幅のスタイルを描画
     *
     * @param string $size
     * @param array $vars
     * @return array
     */
    protected function displaySizeStyleTrait(string $size, array $vars): array
    {
        if ($size) {
            if (is_numeric($size) && intval($size) > 0) {
                $vars['display_size'] = ' style="width: ' . $size . '%"';
            } else {
                $viewClass = ltrim($size, '.');
                $vars['display_size_class'] = ' js_notStyle ' . $viewClass;
            }
        }
        return $vars;
    }

    /**
     * ユニットのサイズ設定を抜き出し
     *
     * @param string $newSize
     * @return array
     */
    protected function extractUnitSizeTrait(string $newSize): array
    {
        if (strpos($newSize, ':') !== false) {
            return array_pad(preg_split('/:/', $newSize), 2, '');
        }
        return ['', ''];
    }
}
