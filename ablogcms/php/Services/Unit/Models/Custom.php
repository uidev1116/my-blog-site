<?php

namespace Acms\Services\Unit\Models;

use Acms\Services\Unit\Contracts\Model;
use Acms\Services\Unit\Contracts\UnitListModule;
use Acms\Services\Unit\Contracts\ExportEntry;
use Acms\Services\Facades\Template as TemplateHelper;
use Acms\Services\Facades\Common;
use Acms\Services\Facades\Entry;
use Acms\Services\Facades\Storage;
use Template;
use Field;
use ACMS_Validator;

class Custom extends Model implements UnitListModule, ExportEntry
{
    use \Acms\Traits\Common\AssetsTrait;

    /**
     * エントリーのエクスポートでエクスポートするアセットを返却
     *
     * @return string[]
     */
    public function exportArchivesFiles(): array
    {
        $field = $this->unserializeField();
        if (empty($field)) {
            return [];
        }
        $exportFiles = [];
        foreach ($field->listFields() as $fd) {
            foreach ($field->getArray($fd, true) as $i => $val) {
                if (empty($val)) {
                    continue;
                }
                if (
                    strpos($fd, '@path') ||
                    strpos($fd, '@tinyPath') ||
                    strpos($fd, '@largePath') ||
                    strpos($fd, '@squarePath')
                ) {
                    $exportFiles[] = $val;
                }
            }
        }
        return $exportFiles;
    }

    /**
     * エントリーのエクスポートでエクスポートするメディアIDを返却
     *
     * @return int[]
     */
    public function exportMediaIds(): array
    {
        $field = $this->unserializeField();
        if (empty($field)) {
            return [];
        }
        $exportMediaIds = [];
        foreach ($field->listFields() as $fd) {
            foreach ($field->getArray($fd, true) as $i => $val) {
                if (empty($val)) {
                    continue;
                }
                if (strpos($fd, '@media') !== false) {
                    $mediaId = intval($val);
                    if ($mediaId > 0) {
                        $exportMediaIds[] = $mediaId;
                    }
                }
            }
        }
        return $exportMediaIds;
    }

    /**
     * エントリーのエクスポートでエクスポートするモジュールIDを返却
     *
     * @return int[]
     */
    public function exportModuleIds(): array
    {
        return [];
    }

    /**
     * Unit_Listモジュールを描画
     *
     * @param Template $tpl
     * @return array
     */
    public function renderUnitListModule(Template $tpl): array
    {
        $field = $this->unserializeField();
        if (empty($field)) {
            return [];
        }
        $block = 'unit#' . $this->getType();
        $tpl->add([$block, 'unit:loop'], TemplateHelper::buildField($field, $tpl, [$block, 'unit:loop']));

        return [];
    }

    /**
     * ユニットタイプを取得
     *
     * @return string
     */
    public function getUnitType(): string
    {
        return 'custom';
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
        $this->setField6(config("{$configKeyPrefix}field_6", '', $configIndex));
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
        $field = Common::extract('unit' . $id, new ACMS_Validator(), new Field());
        $field->retouchCustomUnit($id);
        $this->setField6(acmsSerialize($field));

        if (Entry::isNewVersion()) {
            foreach ($field->listFields() as $fd) {
                if (
                    !strpos($fd, '@path') &&
                    !strpos($fd, '@tinyPath') &&
                    !strpos($fd, '@largePath') &&
                    !strpos($fd, '@squarePath')
                ) {
                    continue;
                }
                $set = false;
                foreach ($field->getArray($fd, true) as $old) {
                    if (in_array($old, Entry::getUploadedFiles(), true)) {
                        continue;
                    }
                    $info = pathinfo($old);
                    $dirname = empty($info['dirname']) ? '' : $info['dirname'] . '/';
                    Storage::makeDirectory(ARCHIVES_DIR . $dirname);
                    $ext = empty($info['extension']) ? '' : '.' . $info['extension'];
                    $newOld = $dirname . uniqueString() . $ext;

                    $path = ARCHIVES_DIR . $old;
                    $newPath = ARCHIVES_DIR . $newOld;
                    copyFile($path, $newPath);

                    if (!$set) {
                        $field->delete($fd);
                        $set = true;
                    }
                    $field->add($fd, $newOld);
                }
            }
            $this->setField6(acmsSerialize($field));
        }
    }

    /**
     * 保存できるユニットか判断
     *
     * @return bool
     */
    public function canSave(): bool
    {
        if (empty($this->getField6())) {
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
        $field = acmsDangerUnserialize($this->getField6());
        if (!($field instanceof Field)) {
            return;
        }
        $this->duplicateFieldsTrait($field);
        $this->setField6(acmsSerialize($field));
    }

    /**
     * ユニット削除時の専用処理
     *
     * @return void
     */
    public function handleRemove(): void
    {
        $field = acmsDangerUnserialize($this->getField6());
        if (!($field instanceof Field)) {
            return;
        }
        $this->removeFieldAssetsTrait($field);
    }

    /**
     * キーワード検索用のワードを取得
     *
     * @return string
     */
    public function getSearchText(): string
    {
        $text = '';
        $field = acmsDangerUnserialize($this->getField6());

        if (!($field instanceof Field)) {
            return '';
        }
        foreach ($field->listFields() as $f) {
            $text .= implode(' ', $field->getArray($f)) . ' ';
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
        $field = $this->unserializeField();
        if (empty($field)) {
            return;
        }
        $vars['attr'] = $this->getAttr();
        $vars['class'] = $this->getAttr(); // legacy
        $vars['align'] = $this->getAlign();
        TemplateHelper::injectMediaField($field, true);
        $block = array_merge(['unit#' . $this->getType()], $rootBlock);
        $vars += TemplateHelper::buildField($field, $tpl, $block, null, [
            'utid' => $this->getId(),
        ]);
        $tpl->add($block, $vars);
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
        $field = $this->unserializeField();
        $block = array_merge([$this->getType()], $rootBlock);
        if ($field) {
            TemplateHelper::injectMediaField($field, true);
            TemplateHelper::injectRichEditorField($field, true);
            $vars += TemplateHelper::buildField($field, $tpl, $block, null, ['id' => $this->getTempId()]);
        }
        $tpl->add($block, $vars);
    }

    /**
     * レガシーなユニットデータを返却（互換性のため）
     *
     * @return array
     */
    protected function getLegacy(): array
    {
        $field = $this->unserializeField();
        return [
            'field' => acmsSerialize($field),
        ];
    }

    /**
     * シリアライズされたフィールドを復元
     *
     * @return Field|null
     */
    protected function unserializeField(): ?Field
    {
        $field = acmsDangerUnserialize($this->getField6());
        if (!$field instanceof Field) {
            return null;
        }
        foreach ($field->listFields() as $fd) {
            if (
                !strpos($fd, '@path') &&
                !strpos($fd, '@tinyPath') &&
                !strpos($fd, '@largePath') &&
                !strpos($fd, '@squarePath')
            ) {
                continue;
            }
            $set = false;
            foreach ($field->getArray($fd, true) as $i => $path) {
                if (!$set) {
                    $field->delete($fd);
                    $set = true;
                }
                $field->add($fd, (string) $path);
            }
        }
        return $field;
    }
}
