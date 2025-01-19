<?php

namespace Acms\Services\Unit\Models;

use Acms\Services\Unit\Contracts\ExportEntry;
use Acms\Services\Unit\Contracts\StaticExport;
use Acms\Services\Unit\Contracts\ValidatePath;
use Acms\Services\Unit\Contracts\Model;
use Acms\Services\Facades\Storage;
use Acms\Services\Facades\Entry;
use ACMS_POST_File;
use Template;

class File extends Model implements ValidatePath, StaticExport, ExportEntry
{
    use \Acms\Traits\Common\AssetsTrait;

    /**
     * ファイルのパスを配列で取得
     *
     * @return array
     */
    public function getFilePaths(): array
    {
        return $this->explodeUnitData($this->getField2());
    }

    /**
     * 静的書き出しで書き出しを行うアセットのパス配列
     *
     * @return array
     */
    public function outputAssetPaths(): array
    {
        return array_map(function ($path) {
            return ARCHIVES_DIR . $path;
        }, $this->explodeUnitData($this->getField2()));
    }

    /**
     * エントリーのエクスポートでエクスポートするアセットを返却
     *
     * @return string[]
     */
    public function exportArchivesFiles(): array
    {
        return $this->explodeUnitData($this->getField2());
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
        return [];
    }

    /**
     * ユニットタイプを取得
     *
     * @return string
     */
    public function getUnitType(): string
    {
        return 'file';
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
        $captions = $_POST["file_caption_{$id}"] ?? null;
        $fileHelper = new ACMS_POST_File($removeOld, $isDirectEdit);
        if (is_array($captions)) {
            // 多言語ユニット
            $files = [];
            $filePathAry = [];
            foreach ($captions as $n => $val) {
                $_old = $_POST["file_old_{$id}"][$n] ?? $_POST["file_old_{$id}"] ?? null;
                $edit = $_POST["file_edit_{$id}"][$n] ?? $_POST['file_edit_' . $id] ?? '';
                if ($_old && !$this->validateRemovePath('file', $_old)) {
                    $_old = null;
                }
                foreach (
                    $fileHelper->buildAndSave(
                        $id,
                        $_old,
                        $_FILES["file_file_{$id}"]['tmp_name'][$n],
                        $_FILES["file_file_{$id}"]['name'][$n],
                        $n,
                        $edit
                    ) as $fileData
                ) {
                    $files[$n] = $fileData;
                }
                if (empty($fileData)) {
                    $files[$n] = '';
                }
            }
            foreach ($files as $filePath) {
                $filePathAry[] = $filePath;
            }
            $this->setField1($this->implodeUnitData($captions));
            $this->setField2($this->implodeUnitData($filePathAry));
        } else {
            // 通常ユニット
            $edit = $_POST["file_edit_{$id}"] ?? '';
            $file = is_array($_FILES["file_file_{$id}"]['tmp_name'] ?? null) ? $_FILES["file_file_{$id}"]['tmp_name'][0] : $_FILES["file_file_{$id}"]['tmp_name'] ?? '';
            $name = is_array($_FILES["file_file_{$id}"]['name'] ?? null) ? $_FILES["file_file_{$id}"]['name'][0] : $_FILES["file_file_{$id}"]['name'] ?? '';
            $old = $_POST["file_old_{$id}"] ?? null;
            if ($old && !$this->validateRemovePath('file', $old)) {
                $old = null;
            }
            $fileData = $fileHelper->buildAndSave(
                $id,
                $old,
                $file,
                $name,
                0,
                $edit
            );
            $fileData = is_array($fileData ?? null) ? $fileData[0] : $fileData ?? '';
            if ($fileData) {
                $this->setField1($_POST["file_caption_{$id}"] ?? '');
                $this->setField2($fileData);
            }
        }

        if (Entry::isNewVersion()) {
            $oldAry = $this->explodeUnitData($this->getField2());
            $newAry = [];
            foreach ($oldAry as $old) {
                if (in_array($old, Entry::getUploadedFiles(), true)) {
                    $newAry[] = $old;
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
                $newAry[] = $newOld;
            }
            $this->setField2($this->implodeUnitData($newAry));
        }
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
        $filePaths = $this->explodeUnitData($this->getField2());
        $newFilePaths = $this->duplicateFilesTrait($filePaths);
        $this->setField2($this->implodeUnitData($newFilePaths));
    }

    /**
     * ユニット削除時の専用処理
     *
     * @return void
     */
    public function handleRemove(): void
    {
        $filePaths = $this->explodeUnitData($this->getField2());
        $this->removeFileAssetsTrait($filePaths);
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
        $path = $this->getField2();
        if (empty($path)) {
            return;
        }
        $pathAry = $this->explodeUnitData($path);

        foreach ($pathAry as $i => $val) {
            $fx = empty($i) ? '' : $i + 1;
            $path = ARCHIVES_DIR . $val;
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            $icon = pathIcon($ext);
            if (!Storage::exists($icon)) {
                continue;
            }
            $vars += [
                'path' . $fx => $path,
                'icon' . $fx => $icon,
                'x' . $fx => 70,
                'y' . $fx => 81,
            ];
            if (config('file_icon_size') === 'dynamic') {
                $xy = Storage::getImageSize($icon);
                $vars['x' . $fx] = $xy[0] ?? 70;
                $vars['y' . $fx] = $xy[1] ?? 81;
            }
        }
        $vars['caption'] = $this->getField1();
        $vars['align'] = $this->getAlign();
        $vars['attr'] = $this->getAttr();
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
        if ($path = $this->getField2()) {
            $vars['old'] = $path;
            $length = count($this->explodeUnitData($path));
            $this->formatMultiLangUnitData($vars['old'], $vars, 'old');

            for ($i = 0; $i < $length; $i++) {
                if (empty($i)) {
                    $fx = '';
                } else {
                    $fx = $i + 1;
                }

                if (!isset($vars['old' . $fx])) {
                    continue;
                }
                $path   = $vars['old' . $fx];
                $vars['basename' . $fx] = Storage::mbBasename($path);

                $e    = preg_replace('@.*\.(?=[^.]+$)@', '', $path);
                $t   = null;
                if (in_array($e, configArray('file_extension_document'), true)) {
                    $t   = 'document';
                } elseif (in_array($e, configArray('file_extension_archive'), true)) {
                    $t   = 'archive';
                } elseif (in_array($e, configArray('file_extension_movie'), true)) {
                    $t   = 'movie';
                } elseif (in_array($e, configArray('file_extension_audio'), true)) {
                    $t   = 'audio';
                }
                $cwd    = getcwd();
                Storage::changeDir(THEMES_DIR . 'system/' . IMAGES_DIR . 'fileicon/');
                $icon   = glob($e . '.*') ? $e : $t;
                Storage::changeDir($cwd);

                $vars['icon' . $fx]   = $icon;
                $vars['type' . $fx]   = $icon;
            }

            $vars['caption'] = $this->getField1();
            $vars['deleteId'] = $this->getTempId();
            $this->formatMultiLangUnitData($vars['caption'], $vars, 'caption');
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
            'caption' => $this->getField1(),
            'path' => $this->getField2(),
        ];
    }
}
