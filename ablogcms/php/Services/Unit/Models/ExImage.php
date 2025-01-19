<?php

namespace Acms\Services\Unit\Models;

use Acms\Services\Unit\Contracts\Model;
use Acms\Traits\Unit\UnitTemplateTrait;
use Acms\Services\Facades\Storage;
use Template;

class ExImage extends Model
{
    use UnitTemplateTrait;

    /**
     * ユニットタイプを取得
     *
     * @return string
     */
    public function getUnitType(): string
    {
        return 'eximage';
    }

    /**
     * ユニットが画像タイプか取得
     *
     * @return bool
     */
    public function getIsImageUnit(): bool
    {
        return false;
    }

    /**
     * ユニットのデフォルト値をセット
     *
     * @param string $configKeyPrefix
     * @param int $configIndex
     * @return void
     */
    public function setDefault(string $configKeyPrefix, int $configIndex): void
    {
        $this->setField1(config("{$configKeyPrefix}field_1", '', $configIndex));
        $this->setField2(config("{$configKeyPrefix}field_2", '', $configIndex));
        $this->setField3(config("{$configKeyPrefix}field_3", '', $configIndex));
        $this->setField4(config("{$configKeyPrefix}field_4", '', $configIndex));
        $this->setField5(config("{$configKeyPrefix}field_5", '', $configIndex));
    }

    /**
     * POSTデータからユニット独自データを抽出
     *
     * @param array $post
     * @param bool $removeOld
     * @param bool $isDirectEdit
     * @return void
     */
    public function extract(array $post, bool $removeOld = true, bool $isDirectEdit = false): void
    {
        $id = $this->getTempId();
        $size = $_POST["eximage_size_{$id}"] ?? '';
        $normal = $_POST["eximage_normal_{$id}"] ?? '';
        $large = $_POST["eximage_large_{$id}"] ?? '';
        $displaySize = '';

        if (strpos($size, ':') !== false) {
            [$size, $displaySize] = preg_split('/:/', $size);
        }
        $normalPath = is_array($normal) ? $normal[0] : $normal;
        $largePath  = is_array($large) ? $large[0] : $large;
        if ('http://' != substr($normalPath, 0, 7) && 'https://' != substr($normalPath, 0, 8)) {
            $normalPath = rtrim(DOCUMENT_ROOT, '/') . $normalPath;
        }
        if ('http://' != substr($largePath, 0, 7) && 'https://' != substr($largePath, 0, 8)) {
            $largePath = rtrim(DOCUMENT_ROOT, '/') . $largePath;
        }
        if ($xy = Storage::getImageSize($normalPath)) {
            if (!empty($size) && ($size < max($xy[0], $xy[1]))) {
                if ($xy[0] > $xy[1]) {
                    $x = $size;
                    $y = intval(floor(($size / $xy[0]) * $xy[1]));
                } else {
                    $y = $size;
                    $x = intval(floor(($size / $xy[1]) * $xy[0]));
                }
            } else {
                [$x, $y] = $xy;
            }
            $size = "{$x}x{$y}";
            if (!Storage::getImageSize($largePath)) {
                $large = '';
            }
        } else {
            $normal = '';
        }
        if (!empty($displaySize)) {
            $size = "{$size}:{$displaySize}";
        }
        $normal = $this->implodeUnitData($normal);
        $large = $this->implodeUnitData($large);

        if ($isDirectEdit && strlen($normal) === 0) {
            $normal = config('action_direct_def_eximage');
            $size = config('action_direct_def_eximage_size');
        }
        $this->setField1($this->implodeUnitData($_POST["eximage_caption_{$id}"] ?? ''));
        $this->setField2($normal);
        $this->setField3($large);
        $this->setField4($this->implodeUnitData($_POST["eximage_link_{$id}"] ?? ''));
        $this->setField5($this->implodeUnitData($_POST["eximage_alt_{$id}"] ?? ''));

        [$size, $displaySize] = $this->extractUnitSizeTrait($size);
        $this->setSize($size);
        $this->setField6($displaySize);
    }

    /**
     * 保存できるユニットか判断
     *
     * @return bool
     */
    public function canSave(): bool
    {
        if (empty($this->getField2())) {
            return false;
        }
        return true;
    }

    /**
     * ユニット複製時の専用処理
     *
     * @return void
     */
    public function handleDuplicate(): void
    {
    }

    /**
     * ユニット削除時の専用処理
     *
     * @return void
     */
    public function handleRemove(): void
    {
    }

    /**
     * キーワード検索用のワードを取得
     *
     * @return string
     */
    public function getSearchText(): string
    {
        return '';
    }

    /**
     * ユニットのサマリーテキストを取得
     *
     * @return string[]
     */
    public function getSummaryText(): array
    {
        return [];
    }

    /**
     * ユニットの描画
     *
     * @param Template $tpl
     * @param array $vars
     * @param string[] $rootBlock
     * @return void
     */
    public function render(Template $tpl, array $vars, array $rootBlock): void
    {
        if (empty($this->getField2())) {
            return;
        }
        [$x, $y] = array_pad(explode('x', $this->getSize()), 2, '');
        $normalAry = $this->explodeUnitData($this->getField2());
        $linkAry = $this->explodeUnitData($this->getField4());
        $largeAry = $this->explodeUnitData($this->getField3());
        foreach ($normalAry as $i => $normal) {
            $j = empty($i) ? '' : $i + 1;
            $eid = $this->getEntryId();
            $link_ = $linkAry[$i] ?? '';
            $large_ = $largeAry[$i] ?? '';
            $url = !empty($link_) ? $link_ : (!empty($large_) ? $large_ : null);

            if (!empty($url)) {
                $linkVars = [
                    "url{$j}" => $url,
                    "link_eid{$j}" => $eid,
                ];
                if (empty($link_)) {
                    $linkVars["viewer{$j}"] = str_replace('{unit_eid}', strval($eid), config('entry_body_image_viewer'));
                }
                $tpl->add(array_merge(["link{$j}#front", 'unit#' . $this->getType()], $rootBlock), $linkVars);
                $tpl->add(array_merge(["link{$j}#rear", 'unit#' . $this->getType()], $rootBlock));
            }
        }
        $vars += [
            'normal' => $this->getField2(),
            'x' => $x,
            'y' => $y,
            'alt' => $this->getField5(),
            'large' => $this->getField3(),
            'caption' => '',
        ];
        $vars = $this->displaySizeStyleTrait($this->getField6(), $vars);
        $vars['caption'] = $this->getField1();
        $vars['align'] = $this->getAlign();
        $vars['attr'] = $this->getAttr();

        $this->formatMultiLangUnitData($vars['normal'], $vars, 'normal');
        $this->formatMultiLangUnitData($x, $vars, 'x');
        $this->formatMultiLangUnitData($y, $vars, 'y');
        $this->formatMultiLangUnitData($vars['alt'], $vars, 'alt');
        $this->formatMultiLangUnitData($vars['large'], $vars, 'large');
        $this->formatMultiLangUnitData($vars['caption'], $vars, 'caption');

        $tpl->add(array_merge(['unit#' . $this->getType()], $rootBlock), $vars);
    }

    /**
     * 編集画面のユニット描画
     *
     * @param Template $tpl
     * @param array $vars
     * @param string[] $rootBlock
     * @return void
     */
    public function renderEdit(Template $tpl, array $vars, array $rootBlock): void
    {
        $size = $this->getSize();
        if ($size) {
            [$x, $y] = array_pad(explode('x', $size), 2, 0);
            $size = max((int) $x, (int) $y);
        }
        $matched = $this->renderSizeSelectTrait($this->getUnitType(), $this->getUnitType(), (string) $size, $tpl, $rootBlock);
        $vars += [
            'caption' => $this->getField1(),
            'large' => $this->getField3(),
            'link' => $this->getField4(),
            'alt' => $this->getAttr(),
        ];
        if ($normal = $this->getField2()) {
            $vars['normal'] = $normal;
        }
        if (!$matched) {
            $vars['size:selected#none'] = config('attr_selected');
        }
        $this->formatMultiLangUnitData($this->getField1(), $vars, 'caption');
        $this->formatMultiLangUnitData($this->getField2(), $vars, 'normal');
        $this->formatMultiLangUnitData($this->getField3(), $vars, 'large');
        $this->formatMultiLangUnitData($this->getField4(), $vars, 'link');
        $this->formatMultiLangUnitData($this->getField5(), $vars, 'alt');

        $tpl->add(array_merge([$this->getUnitType()], $rootBlock), $vars);
    }

    /**
     * レガシーなユニットデータを返却（互換性のため）
     *
     * @return array
     */
    protected function getLegacy(): array
    {
        return [
            'caption' => $this->getField1(),
            'normal' => $this->getField2(),
            'large' => $this->getField3(),
            'link' => $this->getField4(),
            'alt' => $this->getField5(),
            'display_size' => $this->getField6(),
        ];
    }
}
