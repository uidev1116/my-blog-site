<?php

namespace Acms\Services\Unit\Models;

use Acms\Services\Unit\Contracts\Model;
use Acms\Services\Unit\Contracts\ExportEntry;
use Acms\Services\Facades\Template as TemplateHelper;
use Template;

class Module extends Model implements ExportEntry
{
    /**
     * エントリーのエクスポートでエクスポートするアセットを返却
     *
     * @return string[]
     */
    public function exportArchivesFiles(): array
    {
        return [];
    }

    /**
     * エントリーのエクスポートでエクスポートするメディアIDを返却
     *
     * @return int[]
     */
    public function exportMediaIds(): array
    {
        return [];
    }

    /**
     * エントリーのエクスポートでエクスポートするモジュールIDを返却
     *
     * @return int[]
     */
    public function exportModuleIds(): array
    {
        return array_map('intval', $this->explodeUnitData($this->getField1()));
    }

    /**
     * ユニットタイプを取得
     *
     * @return string
     */
    public function getUnitType(): string
    {
        return 'module';
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
        $this->setField1($post["module_mid_{$id}"] ?? '');
        $this->setField2($post["module_tpl_{$id}"] ?? '');
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
        $mid = (int) $this->getField1();
        if (empty($mid)) {
            return;
        }
        $template = $this->getField2();
        $module = loadModule($mid);
        $name = $module->get('name');
        $identifier = $module->get('identifier');
        $vars['view'] = TemplateHelper::spreadModule($name, $identifier, $template);
        $vars['attr'] = $this->getAttr();
        $vars['align'] = $this->getAlign();

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
        $mid = (int) $this->getField1();
        $template = $this->getField2();
        $vars += [
            'mid' => $mid,
            'tpl' => $template,
        ];
        if (!empty($mid)) {
            $module = loadModule($mid);
            $name = $module->get('name');
            $identifier = $module->get('identifier');
            $vars['view'] = TemplateHelper::spreadModule($name, $identifier, $template);
        }
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
            'mid' => $this->getField1(),
            'tpl' => $this->getField2(),
        ];
    }
}
