<?php

namespace Acms\Services\Unit\Models;

use Acms\Services\Unit\Contracts\Model;
use Acms\Traits\Unit\UnitTemplateTrait;
use Template;

class YouTube extends Model
{
    use UnitTemplateTrait;

    /**
     * ユニットタイプを取得
     *
     * @return string
     */
    public function getUnitType(): string
    {
        return 'youtube';
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
        $this->setField2(config("{$configKeyPrefix}field_2", '', $configIndex));
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
        $youtubeId = $this->implodeUnitData($_POST["youtube_id_{$id}"]);
        if ($isDirectEdit && strlen($youtubeId) === 0) {
            $youtubeId = config('action_direct_def_youtubeid');
        }
        if (preg_match(REGEX_VALID_URL, $youtubeId)) {
            $parsed_url = parse_url($youtubeId);
            if (!empty($parsed_url['query'])) {
                $youtubeId = preg_replace('/v=([\w\-_]+).*/', '$1', $parsed_url['query']);
            }
        }
        $this->setField2($youtubeId);
        [$size, $displaySize] = $this->extractUnitSizeTrait($post["youtube_size_{$id}"] ?? '');
        $this->setSize($size);
        $this->setField3($displaySize);
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
        $youtubeId = $this->getField2();
        if (empty($youtubeId)) {
            return;
        }
        list($x, $y) = explode('x', $this->getSize());
        $vars += [
            'youtubeId' => $youtubeId,
            'x' => $x,
            'y' => $y,
            'align' => $this->getAlign(),
        ];
        $this->formatMultiLangUnitData($vars['youtubeId'], $vars, 'youtubeId');
        $vars = $this->displaySizeStyleTrait($this->getField3(), $vars);
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
        $this->renderSizeSelectTrait($this->getUnitType(), $this->getUnitType(), $size, $tpl, $rootBlock);
        $this->formatMultiLangUnitData($this->getField2(), $vars, 'youtubeId');
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
            'youtube_id' => $this->getField2(),
            'display_size' => $this->getField3(),
        ];
    }
}
