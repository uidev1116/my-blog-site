<?php

namespace Acms\Services\Unit\Rendering;

use Acms\Services\Unit\Contracts\Model;
use Acms\Services\Facades\Media;
use Acms\Traits\Unit\UnitTemplateTrait;
use Template;

class Edit
{
    use UnitTemplateTrait;

    /**
     * ユニットのルートブロック
     * @var string[]
     */
    protected $rootBlock = ['unit:loop'];

    /**
     * ユニットの描画
     *
     * @param Model[] $units
     * @param Template $tpl
     * @param ?int $primaryImageUnitId
     * @param string[] $rootBlock
     * @return void
     */
    public function render(array $units, Template $tpl, ?int $primaryImageUnitId, array $rootBlock = []): void
    {
        $unitCount = count($units);
        if ($unitCount > 0) {
            $eagerLoadedMedia = Media::mediaEagerLoadFromUnit($units);
            $enabledUnitGroup = config('unit_group') === 'on';
            foreach ($units as $unit) {
                $unit->setEagerLoadedMedia($eagerLoadedMedia);
                if ($unit->getIsImageUnit()) {
                    // 画像系ユニットの場合、メイン画像情報をセット
                    $unit->setPrimaryImageUnitId($primaryImageUnitId);
                }
                // ユニット独自の描画
                $unit->renderEdit($tpl, [
                    'id' => $unit->getTempId(),
                ], $rootBlock);
                // ソート番号選択肢の描画
                $this->renderSort($tpl, $unit->getSort(), $unitCount, $rootBlock);
                // 配置選択肢の描画
                $this->renderAlign($tpl, $unit, $rootBlock);
                // グループ選択肢の描画
                if ($enabledUnitGroup) {
                    $this->renderGroup($tpl, $unit, $rootBlock);
                }
                // 属性選択肢の描画
                $this->renderAttr($tpl, $unit, $rootBlock);
                // ユニット基本情報の描画
                $tpl->add(array_merge(['column:loop'], $rootBlock), [
                    'uniqid' => $unit->getTempId(),
                    'clid' => $unit->getId(),
                    'cltype' => $unit->getType(),
                    'clattr' => $unit->getAttr(),
                    'clname' => $this->getUnitLabelTrait($unit->getType()),
                ]);
            }
        } else {
            $tpl->add(array_merge(['adminEntryColumn'], $rootBlock));
        }
    }

    /**
     * 新規追加ユニットの描画
     *
     * @param Model[] $units
     * @param int $offset
     * @param Template $tpl
     * @param string[] $rootBlock
     * @return void
     */
    public function renderAddUnit(array $units, int $offset, Template $tpl, array $rootBlock = []): void
    {
        $unitCount = count($units) + $offset;
        $enabledUnitGroup = config('unit_group') === 'on';
        foreach ($units as $i => $unit) {
            // ユニット独自の描画
            $unit->renderEdit($tpl, [
                'id' => $unit->getTempId(),
            ], $rootBlock);
            // ソート番号選択肢の描画
            $this->renderSort($tpl, $offset + $i + 1, $unitCount, $rootBlock);
            // 配置選択肢の描画
            $this->renderAlign($tpl, $unit, $rootBlock);
            // グループ選択肢の描画
            if ($enabledUnitGroup) {
                $this->renderGroup($tpl, $unit, $rootBlock);
            }
            // 属性選択肢の描画
            $this->renderAttr($tpl, $unit, $rootBlock);
            // ユニット基本情報の描画
            $tpl->add(array_merge(['column:loop'], $rootBlock), [
                'uniqid' => $unit->getTempId(),
                'cltype' => $unit->getType(),
                'clname' => $this->getUnitLabelTrait($unit->getType()),
            ]);
        }
    }

    /**
     * ダイレクト編集時のユニット追加・編集の描画
     *
     * @param \Acms\Services\Unit\Contracts\Model $unit
     * @param Template $tpl
     * @param int|null $primaryImageUnitId
     * @param array $rootBlock
     * @return void
     */
    public function renderAddUnitDirect(Model $unit, Template $tpl, ?int $primaryImageUnitId, array $rootBlock = []): void
    {
        if ($unit->getIsImageUnit()) {
            // 画像系ユニットの場合、メイン画像情報をセット
            $unit->setPrimaryImageUnitId($primaryImageUnitId);
        }
        // ユニット独自の描画
        $unit->renderEdit($tpl, [
            'id' => $unit->getTempId(),
        ], $rootBlock);
        // 配置選択肢の描画
        $this->renderAlign($tpl, $unit, $rootBlock);
        // 属性選択肢の描画
        $this->renderAttr($tpl, $unit, $rootBlock);
        // ユニット基本情報の描画
        $tpl->add(array_merge(['column:loop'], $rootBlock), [
            'uniqid' => $unit->getTempId(),
            'cltype' => $unit->getType(),
            'clname' => $this->getUnitLabelTrait($unit->getType()),
            'clid' => $unit->getId(),
        ]);
        // add keep sort & gorup
        $tpl->add(null, [
            'group' => $unit->getGroup(),
            'sort' => $unit->getSort(),
            'post' => implode('/', $_POST),
        ]);
    }

    /**
     * ソート番号選択肢の描画
     *
     * @param Template $tpl
     * @param int $currentNum
     * @param int $unitCount
     * @param string[] $rootBlock
     * @return void
     */
    protected function renderSort(Template $tpl, int $currentNum, int $unitCount, array $rootBlock): void
    {
        $range = range(1, $unitCount);
        array_walk($range, function ($i) use ($currentNum, $tpl, $rootBlock) {
            $vars = [
                'value' => $i,
                'label' => $i,
            ];
            if ($currentNum === $i) {
                $vars['selected'] = config('attr_selected');
            }
            $tpl->add(array_merge(['sort:loop'], $rootBlock), $vars);
        });
    }

    /**
     * 配置選択肢の描画
     *
     * @param Template $tpl
     * @param \Acms\Services\Unit\Contracts\Model $unit
     * @param string[] $rootBlock
     * @return void
     */
    protected function renderAlign(Template $tpl, Model $unit, array $rootBlock = []): void
    {
        if (in_array($unit->getUnitType(), ['text', 'custom', 'module', 'table'], true)) {
            $tpl->add(array_merge(['align#liquid'], $rootBlock), [
                'align:selected#' . $unit->getAlign() => config('attr_selected')
            ]);
        } else {
            $tpl->add(array_merge(['align#solid'], $rootBlock), [
                'align:selected#' . $unit->getAlign() => config('attr_selected')
            ]);
        }
    }

    /**
     * グループ選択肢の描画
     *
     * @param Template $tpl
     * @param \Acms\Services\Unit\Contracts\Model $unit
     * @param string[] $rootBlock
     * @return void
     */

    protected function renderGroup(Template $tpl, Model $unit, array $rootBlock): void
    {
        $labels = configArray('unit_group_label');
        $group = $unit->getGroup();
        array_walk($labels, function ($label, $i) use ($tpl, $group, $rootBlock) {
            $class = config('unit_group_class', '', $i);
            $tpl->add(array_merge(['group:loop'], $rootBlock), [
                'value' => $class,
                'label' => $label,
                'selected' => ($class === $group) ? config('attr_selected') : '',
            ]);
        });
    }

    /**
     * 属性選択肢の描画
     *
     * @param Template $tpl
     * @param \Acms\Services\Unit\Contracts\Model $unit
     * @param string[] $rootBlock
     * @return void
     */
    protected function renderAttr(Template $tpl, Model $unit, array $rootBlock): void
    {
        $type = $unit->getUnitType();
        $currentAttr = $unit->getAttr();
        $aryAttr = configArray('column_' . $type . '_attr');

        if ($aryAttr) {
            array_walk($aryAttr, function ($attr, $i) use ($tpl, $type, $currentAttr, $rootBlock) {
                $label = config('column_' . $type . '_attr_label', '', $i);
                $vars = [
                    'value' => $attr,
                    'label' => $label,
                ];
                if ($attr === $currentAttr) {
                    $vars['selected'] = config('attr_selected');
                }
                $tpl->add(array_merge(['clattr:loop'], $rootBlock), $vars);
            });
        } else {
            $tpl->add(array_merge(['clattr#none'], $rootBlock));
        }
    }
}
