<?php

namespace Acms\Services\Unit\Rendering;

use Acms\Services\Unit\Contracts\Model;
use Acms\Services\Facades\Media;
use Acms\Services\Facades\Entry;
use Template;
use ACMS_RAM;
use Exception;

class Front
{
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
     * @param int $eid
     * @param ?string $preAlign
     * @param bool $renderGroup
     * @return void
     */
    public function render(array $units, Template $tpl, int $eid, ?string $preAlign = null, bool $renderGroup = true): void
    {
        $entry = ACMS_RAM::entry($eid);
        if (is_null($entry)) {
            return;
        }
        $columnAmount = count($units) - 1;
        $currentGroup = null;
        $unitGroupEnable = config('unit_group') === 'on';
        $eagerLoadedMedia = Media::mediaEagerLoadFromUnit($units);
        $isDisplayInvisibleUnit = $this->canDisplayInvisibleUnit(BID, $entry);
        $shouldRenderDirectEdit = $this->shouldRenderDirectEdit();

        foreach ($units as $i => $unit) {
            if (!$isDisplayInvisibleUnit && 'hidden' === $unit->getAlign()) {
                continue; // 非表示ユニット
            }
            $unit->setEagerLoadedMedia($eagerLoadedMedia); // set eager loaded media.
            if ($unitGroupEnable && $renderGroup) {
                // グループ開始
                $currentGroup = $this->renderGroup($tpl, $unit->getGroup(), $currentGroup);
            }
            // クリア・アライン
            $align = $this->renderClear($tpl, $unit, $preAlign);
            // ユニット独自の描画
            $unit->render($tpl, [
                'utid' => $unit->getId(),
                'unit_eid' => $unit->getEntryId(),
            ], $this->rootBlock);
            // ダイレクト編集
            if ($shouldRenderDirectEdit) {
                $this->renderDirectEdit($tpl, $unit, $align);
            }
            // グループ終了
            if ($i === $columnAmount && $currentGroup !== null) {
                $tpl->add(array_merge(['unitGroup#last'], $this->rootBlock));
            }
            $tpl->add($this->rootBlock);
        }
        // ユニットグループでかつ最後の要素が非表示だった場合
        $lastUnit = array_pop($units);
        if (!$isDisplayInvisibleUnit && $lastUnit->getAlign() === 'hidden' && $currentGroup !== null) {
            $tpl->add(array_merge(['unitGroup#last'], $this->rootBlock));
            $tpl->add($this->rootBlock);
        }
    }

    /**
     * サマリーを組み立て
     *
     * @param Model[] $units
     * @return array
     */
    public function renderSummaryText(array $units): array
    {
        $textData = [];
        foreach ($units as $unit) {
            if ($unit->getAlign() === 'hidden') {
                continue;
            }
            $data = $unit->getSummaryText();
            foreach ($data as $i => $txt) {
                if (isset($textData[$i])) {
                    $textData[$i] .= "{$txt} ";
                } else {
                    $textData[] = "{$txt} ";
                }
            }
        }
        return $textData;
    }

    /**
     * ユニットグループを描画
     *
     * @param Template $tpl
     * @param string $group
     * @param string|null $currentGroup
     * @return ?string
     */
    protected function renderGroup(Template $tpl, string $group, ?string $currentGroup): ?string
    {
        if (empty($group)) {
            return $currentGroup;
        }
        $class = $group;
        $count = 0;

        // close rear
        if (!!$currentGroup) {
            $tpl->add(['unitGroup#rear', 'unit:loop']);
        }
        // open front
        $grVars = ['class' => $class];
        if ($currentGroup === $class) {
            $count += 1;
            $grVars['i'] = $count;
        } else {
            $count = 1;
            $grVars['i'] = $count;
        }

        if ($class === config('unit_group_clear', 'acms-column-clear')) {
            $currentGroup = null;
        } else {
            $tpl->add(array_merge(['unitGroup#front'], $this->rootBlock), $grVars);
            $currentGroup = $class;
        }
        return $currentGroup;
    }

    /**
     * Clearの描画
     *
     * @param Template $tpl
     * @param Model $unit
     * @param string|null $preAlign
     * @return string|null
     */
    protected function renderClear(Template $tpl, Model $unit, ?string &$preAlign): ?string
    {
        $type = $unit->getUnitType();
        $align = $unit->getAlign();
        if ($type === 'break') {
            return $align;
        }
        (function () use ($align, $preAlign, $type, $tpl) {
            if (empty($preAlign)) {
                return;
            };
            if ($align === 'left' && $preAlign === 'left') {
                return;
            };
            if ($align === 'right' && $preAlign === 'right') {
                return;
            }
            if ($align === 'auto') {
                if ($preAlign === 'left') {
                    return;
                }
                if ($preAlign === 'right') {
                    return;
                }
                if ($preAlign === 'auto' && $type === 'text') {
                    return;
                }
            }
            $tpl->add(array_merge(['clear'], $this->rootBlock));
        })();

        if ($align === 'auto' && $type !== 'text') {
            $unit->setAlign(!empty($preAlign) ? $preAlign : 'auto');
        }
        $preAlign = $align;

        return $align;
    }

    /**
     *
     * @param Template $tpl
     * @param Model $unit
     * @param string|null $align
     * @return void
     * @throws Exception
     */
    protected function renderDirectEdit(Template $tpl, Model $unit, ?string $align): void
    {
        $vars = [];
        $vars['unit:loop.type'] = $unit->getUnitType();
        $vars['unit:loop.utid'] = $unit->getId();
        $vars['unit:loop.unit_eid'] = $unit->getEntryId();
        $vars['unit:loop.sort'] = $unit->getSort();
        $vars['unit:loop.align'] = $align;
        $tpl->add(array_merge(['inplace#front'], $this->rootBlock), $vars);
        $tpl->add(array_merge(['inplace#rear'], $this->rootBlock));
    }

    /**
     * ダイレクト編集のためのブロック・変数を描画するかどうか
     *
     * @return bool
     */
    protected function shouldRenderDirectEdit(): bool
    {
        return Entry::isDirectEditEnabled();
    }

    /**
     * 非表示ユニットを表示するかどうか
     *
     * @param int $bid
     * @param array $entry
     * @return bool
     */
    protected function canDisplayInvisibleUnit(int $bid, array $entry): bool
    {
        return sessionWithContribution($bid) && // @phpstan-ignore-line
            roleEntryUpdateAuthorization($bid, $entry) &&
            'on' === config('entry_edit_inplace_enable') &&
            'on' === config('entry_edit_inplace') &&
            (!enableApproval() || sessionWithApprovalAdministrator()) &&
            $entry['entry_approval'] !== 'pre_approval' &&
            VIEW === 'entry'; // @phpstan-ignore-line
    }
}
