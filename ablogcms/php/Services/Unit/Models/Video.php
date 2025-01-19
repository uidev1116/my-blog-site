<?php

namespace Acms\Services\Unit\Models;

use Acms\Services\Unit\Contracts\Model;
use Acms\Traits\Unit\UnitTemplateTrait;
use Template;
use ACMS_Hook;

class Video extends Model
{
    use UnitTemplateTrait;

    /**
     * ユニットタイプを取得
     *
     * @return string
     */
    public function getUnitType(): string
    {
        return 'video';
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
        $videoId = $this->implodeUnitData($_POST["video_id_{$id}"]);
        if ($isDirectEdit && strlen($videoId) === 0) {
            $videoId = config('action_direct_def_videoid');
        }
        if (preg_match(REGEX_VALID_URL, $videoId)) {
            $tempVideoId = '';
            if (HOOK_ENABLE) {
                $Hook = ACMS_Hook::singleton();
                $Hook->call('extendsVideoUnit', [$videoId, &$tempVideoId]);
            }
            if (is_string($tempVideoId) && $tempVideoId !== '') { // @phpstan-ignore-line
                $videoId = $tempVideoId;
            } else {
                $parsed_url = parse_url($videoId);
                if (!empty($parsed_url['query'])) {
                    $videoId = preg_replace('/v=([\w\-_]+).*/', '$1', $parsed_url['query']);
                }
            }
        }
        $this->setField2($videoId);
        [$size, $displaySize] = $this->extractUnitSizeTrait($post["video_size_{$id}"] ?? '');
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
        [$x, $y] = array_pad(explode('x', $this->getSize()), 2, '');
        $vars += [
            'videoId' => $youtubeId,
            'x' => $x,
            'y' => $y,
            'align' => $this->getAlign(),
        ];
        $this->formatMultiLangUnitData($vars['videoId'], $vars, 'videoId');
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
        $this->renderSizeSelectTrait($this->getUnitType(), $this->getUnitType(), $this->getSize(), $tpl, $rootBlock);
        $this->formatMultiLangUnitData($this->getField2(), $vars, 'videoId');

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
            'video_id' => $this->getField2(),
            'display_size' => $this->getField3(),
        ];
    }
}
