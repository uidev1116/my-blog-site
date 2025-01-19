<?php

namespace Acms\Services\Unit\Models;

use Acms\Services\Unit\Contracts\ExportEntry;
use Acms\Services\Unit\Contracts\PrimaryImageUnit;
use Acms\Services\Unit\Contracts\UnitListModule;
use Acms\Services\Unit\Contracts\ValidatePath;
use Acms\Services\Unit\Contracts\Model;
use Acms\Services\Facades\Storage;
use Acms\Services\Facades\Entry;
use ACMS_POST_Image;
use Template;

class Image extends Model implements PrimaryImageUnit, ValidatePath, UnitListModule, ExportEntry
{
    use \Acms\Traits\Unit\UnitTemplateTrait;
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
     * メイン画像のパスを取得。メディアの場合メディアIDを取得
     *
     * @return array
     */
    public function getPaths(): array
    {
        return $this->explodeUnitData($this->getField2());
    }

    /**
     * メイン画像のAltを取得
     *
     * @return array
     */
    public function getAlts(): array
    {
        return $this->explodeUnitData($this->getField4());
    }

    /**
     * メイン画像のキャプションを取得
     *
     * @return array
     */
    public function getCaptions(): array
    {
        return $this->explodeUnitData($this->getField1());
    }

    /**
     * エントリーのエクスポートでエクスポートするアセットを返却
     *
     * @return string[]
     */
    public function exportArchivesFiles(): array
    {
        $paths = $this->explodeUnitData($this->getField2());
        $exportFiles = [];
        foreach ($paths as $path) {
            $exportFiles[] = $path;
            $exportFiles[] = otherSizeImagePath($path, 'large');
            $exportFiles[] = otherSizeImagePath($path, 'tiny');
            $exportFiles[] = otherSizeImagePath($path, 'square');
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
     * Unit_Listモジュールを描画
     *
     * @param Template $tpl
     * @return array
     */
    public function renderUnitListModule(Template $tpl): array
    {
        $vars = [];
        $path = $this->explodeUnitData($this->getField2());
        $normal = $path[0] ?? $path;
        $tiny = preg_replace('@(^|/)(?=[^/]+$)@', '$1tiny-', $normal);
        $large = preg_replace('@(^|/)(?=[^/]+$)@', '$1large-', $normal);
        $square = preg_replace('@(^|/)(?=[^/]+$)@', '$1square-', $normal);

        $vars['tiny'] = $tiny;
        $vars['normal'] = $normal;
        if (Storage::isFile(ARCHIVES_DIR . $large)) {
            $vars['large'] = $large;
        }
        if (Storage::isFile(ARCHIVES_DIR . $square)) {
            $vars['square'] = $square;
        }
        return $vars;
    }

    /**
     * ユニットタイプを取得
     *
     * @return string
     */
    public function getUnitType(): string
    {
        return 'image';
    }

    /**
     * ユニットが画像タイプか取得
     *
     * @return bool
     */
    public function getIsImageUnit(): bool
    {
        return true;
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
        $captions = $_POST["image_caption_{$id}"] ?? null;
        $imageHelper = new ACMS_POST_Image($removeOld, $isDirectEdit);
        $imageFiles = [];

        if (is_array($captions)) {
            // 多言語ユニット
            $imagePathAry = [];
            $exifAry = [];
            foreach ($captions as $n => $val) {
                $_old = $_POST["image_old_{$id}"][$n] ?? $_POST["image_old_{$id}"] ?? null;
                $edit = $_POST["image_edit_{$id}"][$n] ?? $_POST['image_edit_' . $id];
                if ($_old && !$this->validateRemovePath('image', $_old)) {
                    $_old = null;
                }
                if ($postFile = $_POST["image_file_{$id}"][$n] ?? false) {
                    $imageHelper = new ACMS_POST_Image($removeOld, true);
                    ACMS_POST_Image::base64DataToImage($postFile, "image_file_{$id}", $n);
                }
                $tmp = $_FILES["image_file_{$id}"]['tmp_name'][$n] ?? '';
                $exifData = $_POST["image_exif_{$id}"] ?? [];
                foreach (
                    $imageHelper->buildAndSave(
                        $id,
                        $_old,
                        $tmp,
                        $_POST["image_size_{$id}"],
                        $edit,
                        $_POST["old_image_size_{$id}"]
                    ) as $imageData
                ) {
                    $exif = array_shift($exifData);
                    $imageData['exif'] = $exif;
                    $imageFiles[$n] = $imageData;
                }
                if (empty($imageFiles[$n])) {
                    $imageFiles[$n] = [
                        'path' => '',
                        'exif' => '',
                    ];
                }
            }
            foreach ($imageFiles as $imagePath) {
                $imagePathAry[] = $imagePath['path'];
                $exifAry[] = $imagePath['exif'];
            }
            $this->setField1($this->implodeUnitData($captions));
            $this->setField2($this->implodeUnitData($imagePathAry));
            $this->setField3($this->implodeUnitData($_POST["image_link_{$id}"]));
            $this->setField4($this->implodeUnitData($_POST["image_alt_{$id}"]));
            $this->setField6($this->implodeUnitData($exifAry));
        } else {
            // 通常ユニット
            $old = $_POST["image_old_{$id}"] ?? null;
            if ($old && !$this->validateRemovePath('image', $old)) {
                $old = null;
            }
            $imageFile = is_array($_POST["image_file_{$id}"] ?? null) ? $_POST["image_file_{$id}"][0] : $_POST["image_file_{$id}"] ?? '';
            if ($imageFile) {
                $imageHelper = new ACMS_POST_Image($removeOld, true);
                ACMS_POST_Image::base64DataToImage($imageFile, "image_file_{$id}");
            }
            $tmp = is_array($_FILES["image_file_{$id}"]['tmp_name'] ?? null) ? $_FILES["image_file_{$id}"]['tmp_name'][0] : $_FILES["image_file_{$id}"]['tmp_name'] ?? '';
            $exif = is_array($_POST["image_exif_{$id}"] ?? null) ? $_POST["image_exif_{$id}"][0] : $_POST["image_exif_{$id}"] ?? '';
            $oldSize = $_POST["old_image_size_{$id}"] ?? '';

            $imageData = $imageHelper->buildAndSave(
                $id,
                $old,
                $tmp,
                $_POST["image_size_{$id}"],
                $_POST["image_edit_{$id}"],
                $oldSize
            );
            $imageData = is_array($imageData) ? $imageData[0] : $imageData ?? '';

            $this->setField1($_POST["image_caption_{$id}"] ?? '');
            $this->setField2($imageData['path'] ?? '');
            $this->setField3($_POST["image_link_{$id}"] ?? '');
            $this->setField4($_POST["image_alt_{$id}"] ?? '');
            $this->setField6($exif);
        }

        [$size, $displaySize] = $this->extractUnitSizeTrait($post["image_size_{$id}"] ?? '');
        $this->setSize($size);
        $this->setField5($displaySize);

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
                $large = otherSizeImagePath($path, 'large');
                $tiny = otherSizeImagePath($path, 'tiny');
                $square = otherSizeImagePath($path, 'square');

                $newPath = ARCHIVES_DIR . $newOld;
                $newLarge = otherSizeImagePath($newPath, 'large');
                $newTiny = otherSizeImagePath($newPath, 'tiny');
                $newSquare = otherSizeImagePath($newPath, 'square');

                copyFile($path, $newPath);
                copyFile($large, $newLarge);
                copyFile($tiny, $newTiny);
                copyFile($square, $newSquare);

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
        $imagePaths = explodeUnitData($this->getField2());
        $newImagePaths = $this->duplicateImagesTrait($imagePaths);
        $this->setField2($this->implodeUnitData($newImagePaths));
    }

    /**
     * ユニット削除時の専用処理
     *
     * @return void
     */
    public function handleRemove(): void
    {
        $imagePaths = $this->explodeUnitData($this->getField2());
        $this->removeImageAssetsTrait($imagePaths);
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

        foreach ($pathAry as $i => $path_) {
            if (empty($i)) {
                $i = '';
            } else {
                $i++;
            }
            $path = ARCHIVES_DIR . $path_;
            $xy = Storage::getImageSize($path);
            $vars['path' . $i] = $path;
            $vars['x' . $i] = $xy[0] ?? '';
            $vars['y' . $i] = $xy[1] ?? '';
        }
        $vars['alt'] = $this->getField4();
        $vars['exif'] = $this->getField6();
        $vars = $this->displaySizeStyleTrait($this->getField5(), $vars);
        $vars['caption'] = $this->getField1();
        $vars['align'] = $this->getAlign();
        $vars['attr'] = $this->getAttr();

        $linkAry = $this->explodeUnitData($this->getField3());
        $path = '';
        foreach ($pathAry as $i => $path_) {
            $j = empty($i) ? '' : $i + 1;
            $link_ = $linkAry[$i] ?? '';
            $eid = $this->getEntryId();
            if (empty($link_)) {
                if ($pathAry[$i]) {
                    $path = ARCHIVES_DIR . $pathAry[$i];
                } else {
                    $path = ARCHIVES_DIR . $this->getField2();
                }
                $name = Storage::mbBasename($path);
                $large = substr($path, 0, strlen($path) - strlen($name)) . 'large-' . $name;
                if ($xy = Storage::getImageSize($large)) {
                    $tpl->add(
                        array_merge(['link' . $j . '#front', 'unit#' . $this->getType()], $rootBlock),
                        [
                            'url' . $j => BASE_URL . $large,
                            'viewer' . $j => str_replace('{unit_eid}', strval($eid), config('entry_body_image_viewer')),
                            'caption' . $j => $this->getField1(),
                            'link_eid' . $j => $eid
                        ]
                    );
                    $tpl->add(array_merge(['link' . $j . '#rear', 'unit#' . $this->getType()], $rootBlock));
                }
            } else {
                $tpl->add(array_merge(['link' . $j . '#front', 'unit#' . $this->getType()], $rootBlock), [
                    'url' . $j => $link_,
                ]);
                $tpl->add(array_merge(['link' . $j . '#rear', 'unit#' . $this->getType()], $rootBlock));
            }
        }
        if ($path !== '') {
            $tiny = otherSizeImagePath($path, 'tiny');
            if ($xy = Storage::getImageSize($tiny)) {
                $vars['tinyPath'] = $tiny;
                $vars['tinyX'] = $xy[0] ?? '';
                $vars['tinyY'] = $xy[1] ?? '';
            }
            $square = otherSizeImagePath($path, 'square');
            $squareImgSize = config('image_size_square');
            if (Storage::isFile($square)) {
                $vars['squarePath'] = $square;
                $vars['squareX'] = $squareImgSize;
                $vars['squareY'] = $squareImgSize;
            }
            $large = otherSizeImagePath($path, 'large');
            if ($xy = Storage::getImageSize($large)) {
                $vars['largePath'] = $large;
                $vars['largeX'] = $xy[0] ?? '';
                $vars['largeY'] = $xy[1] ?? '';
            }
        }
        foreach ($vars as $key => $val) {
            $this->formatMultiLangUnitData($val, $vars, $key);
        }
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
        $path = $this->getField2();
        $this->renderSizeSelectTrait($this->getUnitType(), $this->getUnitType(), $size, $tpl, $rootBlock);

        $vars += [
            'old' => $path,
            'size_old' => $size . ':' . $this->getField5(),
            'caption' => $this->getField1(),
            'link' => $this->getField3(),
            'alt' => $this->getField4(),
            'exif' => $this->getField6(),
        ];
        $this->formatMultiLangUnitData($vars['old'], $vars, 'old');
        $this->formatMultiLangUnitData($vars['caption'], $vars, 'caption');
        $this->formatMultiLangUnitData($vars['exif'], $vars, 'exif');
        $this->formatMultiLangUnitData($vars['link'], $vars, 'link');
        $this->formatMultiLangUnitData($vars['alt'], $vars, 'alt');

        if ($editAction = $this->getEditAction()) {
            $vars['edit:selected#' . $editAction] = config('attr_selected');
        }
        // tiny and large
        if ($path) {
            $nXYAry     = [];
            $tXYAry     = [];
            $tinyAry    = [];
            $lXYAry     = [];
            foreach ($this->explodeUnitData($path) as $normal) {
                $nXY   = Storage::getImageSize(ARCHIVES_DIR . $normal);
                $tiny  = preg_replace('@[^/]+$@', 'tiny-$0', $normal);
                $large = preg_replace('@[^/]+$@', 'large-$0', $normal);
                $tXY   = Storage::getImageSize(ARCHIVES_DIR . $tiny);
                if ($lXY = Storage::getImageSize(ARCHIVES_DIR . $large)) {
                    $lXYAry['x'][] = $lXY[0];
                    $lXYAry['y'][] = $lXY[1];
                } else {
                    $lXYAry['x'][] = '';
                    $lXYAry['y'][] = '';
                }
                $nXYAry['x'][] = $nXY[0] ?? '';
                $nXYAry['y'][] = $nXY[1] ?? '';
                $tXYAry['x'][] = $tXY[0] ?? '';
                $tXYAry['y'][] = $tXY[1] ?? '';
                $tinyAry[] = $tiny;
            }
            $popup = otherSizeImagePath($path, 'large');
            if (!Storage::getImageSize(ARCHIVES_DIR . $popup)) {
                $popup = $path;
            }
            $vars += [
                'tiny' => implodeUnitData($tinyAry),
                'tinyX' => implodeUnitData($tXYAry['x']),
                'tinyY' => implodeUnitData($tXYAry['y']),
                'popup' => $popup,
                'normalX' => implodeUnitData($nXYAry['x']),
                'normalY' => implodeUnitData($nXYAry['y']),
                'largeX' => implodeUnitData($lXYAry['x']),
                'largeY' => implodeUnitData($lXYAry['y']),
            ];
            $this->formatMultiLangUnitData($vars['tiny'], $vars, 'tiny');
            $this->formatMultiLangUnitData($vars['tinyX'], $vars, 'tinyX');
            $this->formatMultiLangUnitData($vars['popup'], $vars, 'popup');
            $this->formatMultiLangUnitData($vars['normalX'], $vars, 'normalX');
            $this->formatMultiLangUnitData($vars['normalY'], $vars, 'normalY');
            $this->formatMultiLangUnitData($vars['largeX'], $vars, 'largeX');
            $this->formatMultiLangUnitData($vars['largeY'], $vars, 'largeY');

            foreach ($vars as $key => $val) {
                if ($val == '') {
                    unset($vars[$key]);
                }
            }
        } else {
            $tpl->add(array_merge(['preview#none', $this->getUnitType()], $rootBlock));
        }
        // rotate
        if (function_exists('imagerotate')) {
            $count = count($this->explodeUnitData($path));
            for ($i = 0; $i < $count; $i++) {
                if (empty($i)) {
                    $n = '';
                } else {
                    $n = $i + 1;
                }
                $tpl->add(array_merge(['rotate' . $n, $this->getUnitType()], $rootBlock));
            }
        }
        // primary image
        if ($primaryImageUnitId = $this->getPrimaryImageUnitId()) {
            $unitId = $this->getId();
            $vars['primaryImageId'] = $this->getTempId();
            if ($unitId && $primaryImageUnitId === $unitId) {
                $vars['primaryImageChecked'] = config('attr_checked');
            }
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
            'link' => $this->getField3(),
            'alt' => $this->getField4(),
            'exif' => $this->getField6(),
            'display_size' => $this->getField5(),
        ];
    }
}
