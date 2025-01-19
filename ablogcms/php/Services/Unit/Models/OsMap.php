<?php

namespace Acms\Services\Unit\Models;

use Acms\Services\Unit\Contracts\Model;
use Acms\Traits\Unit\UnitTemplateTrait;
use Template;

class OsMap extends Model
{
    use UnitTemplateTrait;

    /**
     * ユニットタイプを取得
     *
     * @return string
     */
    public function getUnitType(): string
    {
        return 'osmap';
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
     * 吹き出しHTMLを取得
     *
     * @return string
     */
    public function getMessage(): string
    {
        return str_replace([
            '"', '<', '>', '&'
        ], [
            '[[:quot:]]', '[[:lt:]]', '[[:gt:]]', '[[:amp:]]'
        ], $this->getField1());
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
        $this->setField2(config("{$configKeyPrefix}field_2", '35.185574', $configIndex));
        $this->setField3(config("{$configKeyPrefix}field_3", '136.899066', $configIndex));
        $this->setField4(config("{$configKeyPrefix}field_4", '10', $configIndex));
        $this->setField6(config("{$configKeyPrefix}field_6", '', $configIndex));
        $this->setField7(config("{$configKeyPrefix}field_7", '', $configIndex));
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
        $this->setField1($post["map_msg_{$id}"] ?? '');
        $this->setField2($post["map_lat_{$id}"] ?? '');
        $this->setField3($post["map_lng_{$id}"] ?? '');
        $this->setField4($post["map_zoom_{$id}"] ?? '');

        [$size, $displaySize] = $this->extractUnitSizeTrait($post["map_size_{$id}"] ?? '');
        $this->setSize($size);
        $this->setField5($displaySize);
    }

    /**
     * 保存できるユニットか判断
     *
     * @return bool
     */
    public function canSave(): bool
    {
        if (
            empty($this->getField1()) &&
            empty($this->getField2()) &&
            empty($this->getField3()) &&
            empty($this->getField4())
        ) {
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
        $vars += $this->formatData();
        $vars = $this->displaySizeStyleTrait($this->getField5(), $vars);
        $vars['attr'] = $this->getAttr();
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
        $this->renderSizeSelectTrait('map', $this->getUnitType(), $size, $tpl, $rootBlock);
        $vars += $this->formatData();
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
            'msg' => $this->getField1(),
            'lat' => $this->getField2(),
            'lng' => $this->getField3(),
            'zoom' => $this->getField4(),
            'display_size' => $this->getField5(),
        ];
    }

    /**
     * データを整形
     *
     * @return array
     */
    protected function formatData(): array
    {
        list($x, $y) = array_pad(explode('x', $this->getSize()), 2, '');
        return [
            'lat' => $this->getField2(),
            'lng' => $this->getField3(),
            'zoom' => $this->getField4(),
            'msg' => $this->getMessage(),
            'msgRaw' => $this->getField1(),
            'x' => $x,
            'y' => $y,
            'align' => $this->getAlign(),
        ];
    }
}
