<?php

namespace Acms\Services\Unit\Models;

use Acms\Services\Unit\Contracts\Model;
use Acms\Services\Unit\Contracts\UnitSetting;
use Acms\Services\Facades\Common;
use Template;
use Field;
use ACMS_Corrector;

class Text extends Model implements UnitSetting
{
    /**
     * ユニット設定の専用コンフィグ設定を描画
     *
     * @param Template $tpl
     * @param Field $config
     * @return void
     */
    public function renderUnitSettings(Template $tpl, Field $config): void
    {
        foreach ($config->getArray('column_text_tag') as $i => $tag) {
            $tpl->add(['textTag:loop', $this->getUnitType()], [
                'value' => $tag,
                'label' => $config->get('column_text_tag_label', '', $i),
            ]);
        }
    }

    /**
     * ユニットタイプを取得
     *
     * @return string
     */
    public function getUnitType(): string
    {
        return 'text';
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
        $this->setField3('');
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
        $tokens = preg_split('@(#|\.)@', $post["text_tag_{$id}"] ?? '', -1, PREG_SPLIT_DELIM_CAPTURE);
        $this->setField2(array_shift($tokens));

        $idStr = '';
        $classStr = '';
        $attr = '';
        while ($mark = array_shift($tokens)) {
            $val = array_shift($tokens) ?: '';
            if ('#' === $mark) {
                $idStr = $val;
            } else {
                $classStr = $val;
            }
        }
        $attr .= !empty($idStr) ? " id=\"{$idStr}\"" : "";
        $attr .= !empty($classStr) ? " class=\"{$classStr}\"" : "";
        if (!empty($attr)) {
            $this->setAttr($attr);
        }
        if (isset($post["text_extend_tag_{$id}"])) {
            $this->setField3($post["text_extend_tag_{$id}"] ?? '');
        }
        $text = $this->implodeUnitData($post["text_text_{$id}"] ?? '');
        if ($isDirectEdit && strlen($text) === 0) {
            $text = config('action_direct_def_text');
        }
        $this->setField1($text);
    }

    /**
     * 保存できるユニットか判断
     *
     * @return bool
     */
    public function canSave(): bool
    {
        if (empty($this->getField1())) {
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
        $text = $this->getField1();
        if ('markdown' === $this->getField2()) {
            $text = Common::parseMarkdown($text);
        }
        return $text;
    }

    /**
     * ユニットのサマリーテキストを取得
     *
     * @return string[]
     */
    public function getSummaryText(): array
    {
        $textAry = $this->explodeUnitData($this->getField1());
        $response = [];
        foreach ($textAry as $text) {
            if ($this->getField2() === 'markdown') {
                $text = Common::parseMarkdown($text);
            } elseif ($this->getField2() === 'table') {
                $corrector = new ACMS_Corrector();
                $text = $corrector->table($text);
            }
            $text = preg_replace('@\s+@u', ' ', strip_tags($text));
            $response[] = $text;
        }
        return $response;
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
        if (empty($this->getField1())) {
            return;
        }
        $vars += [
            'text' => $this->getField1(),
            'extend_tag' => $this->getField3(),
        ];
        $this->formatMultiLangUnitData($vars['text'], $vars, 'text');

        $attr = $this->getAttr();
        if (!empty($attr)) {
            $vars['attr'] = $attr;
            $vars['class'] = $attr; // legacy
        }
        $vars['extend_tag'] = $this->getField3();
        $tpl->add(array_merge([$this->getField2(), 'unit#' . $this->getType()], $rootBlock), $vars);
        $tpl->add(array_merge(['unit#' . $this->getType()], $rootBlock), [
            'align' => $this->getAlign(),
        ]);
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
        $suffix = '';
        $attr = $this->getAttr();
        $currentTag = $this->getField2();

        if (preg_match('@(?:id="([^"]+)"|class="([^"]+)")@', $attr, $match)) {
            if (!empty($match[1])) {
                $suffix .= '#' . $match[1];
            }
            if (!empty($match[2])) {
                $suffix .= '.' . $match[2];
            }
        }
        foreach (configArray('column_text_tag') as $i => $tag) {
            $tagSelectVars = [
                'value' => $tag,
                'label' => config('column_text_tag_label', '', $i),
                'extend' => config('column_text_tag_extend_label', '', $i),
            ];
            if ($currentTag . $suffix === $tag) {
                $tagSelectVars['selected'] = config('attr_selected');
            }
            $tpl->add(array_merge(['textTag:loop', $this->getUnitType()], $rootBlock), $tagSelectVars);
        }
        $vars += [
            'extend_tag' => $this->getField3(),
        ];
        $this->formatMultiLangUnitData($this->getField1(), $vars, 'text');

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
            'text' => $this->getField1(),
            'tag' => $this->getField2(),
            'extend_tag' => $this->getField3(),
        ];
    }
}
