<?php

namespace Acms\Services\Unit\Models;

use Acms\Services\Unit\Contracts\ExportEntry;
use Acms\Services\Unit\Contracts\PrimaryImageUnit;
use Acms\Services\Unit\Contracts\UnitListModule;
use Acms\Services\Unit\Contracts\Model;
use Acms\Services\Facades\Media as MediaHelper;
use Acms\Services\Facades\Storage;
use Acms\Services\Facades\Database;
use Acms\Traits\Unit\UnitTemplateTrait;
use Template;
use DOMDocument;
use SQL;

class Media extends Model implements PrimaryImageUnit, UnitListModule, ExportEntry
{
    use UnitTemplateTrait;

    /**
     * メイン画像のパスを取得。メディアの場合メディアIDを取得
     *
     * @return array
     */
    public function getPaths(): array
    {
        return $this->explodeUnitData($this->getField1());
    }

    /**
     * メイン画像のAltを取得
     *
     * @return array
     */
    public function getAlts(): array
    {
        return $this->explodeUnitData($this->getField3());
    }

    /**
     * メイン画像のキャプションを取得
     *
     * @return array
     */
    public function getCaptions(): array
    {
        return $this->explodeUnitData($this->getField2());
    }

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
        return array_map('intval', $this->explodeUnitData($this->getField1()));
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
        $data = $this->explodeUnitData($this->getField1());
        $mediaId = $data[0] ?? $data;
        if (empty($mediaId)) {
            return [];
        }
        $vars = [];
        $eagerLoadedMedia = $this->getEagerLoadedMedia();
        if (isset($eagerLoadedMedia[$mediaId])) {
            $media = $eagerLoadedMedia[$mediaId];
            $mediaType = $media['media_type'];
            if ($mediaType === 'image') {
                $vars['normal'] = MediaHelper::urlencode($media['media_path']) . MediaHelper::cacheBusting($media['media_update_date']);
                $vars['large'] = MediaHelper::urlencode($media['media_original']) . MediaHelper::cacheBusting($media['media_update_date']);
            } elseif ($mediaType === 'file') {
                if (empty($media['media_status'])) {
                    $vars['download'] = '/' . MediaHelper::getFileOldPermalink(MediaHelper::urlencode($media['media_path']), false);
                } else {
                    $vars['download'] = '/' . MediaHelper::getFilePermalink($media['media_id'], false);
                }
            }
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
        return 'media';
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
        $this->setField5(config("{$configKeyPrefix}field_5", '', $configIndex));
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
        $this->setField1($this->implodeUnitData($post["media_id_{$id}"] ?? ''));
        $this->setField2($this->implodeUnitData($post["media_caption_{$id}"] ?? ''));
        $this->setField3($this->implodeUnitData($post["media_alt_{$id}"] ?? ''));
        $this->setField4($this->implodeUnitData($post["media_enlarged_{$id}"] ?? ''));
        $this->setField5($this->implodeUnitData($post["media_use_icon_{$id}"] ?? ''));
        $this->setField7($this->implodeUnitData($post["media_link_{$id}"] ?? ''));
        [$size, $displaySize] = $this->extractUnitSizeTrait($this->implodeUnitData($post["media_size_{$id}"] ?? ''));
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
        if (empty($this->getField1())) {
            return;
        }
        $varsRoot = $vars;
        $midAry = $this->explodeUnitData($this->getField1());
        $mediaCaptions = $this->explodeUnitData($this->getField2());
        $mediaAlts = $this->explodeUnitData($this->getField3());
        $mediaSizes = $this->explodeUnitData($this->getSize());
        $mediaAlign = $this->getAlign();
        $mediaAttr = $this->getAttr();
        $mediaLarges = $this->explodeUnitData($this->getField4());
        $mediaUseIcons = $this->explodeUnitData($this->getField5());
        $displaySize = $this->getField6();
        $mediaLinks = $this->explodeUnitData($this->getField7());
        $eagerLoadedMedia = $this->getEagerLoadedMedia();
        $actualType = $this->getType();

        foreach ($midAry as $i => $mid) {
            $fx = empty($i) ? '' : $i + 1;
            $mid = (int) $mid;
            $vars = [];

            if (!isset($eagerLoadedMedia[$mid])) {
                continue;
            }
            $media = $eagerLoadedMedia[$mid];
            $path = MediaHelper::urlencode($media['media_path']);
            $type = $media['media_type'];

            $vars["caption{$fx}"] = ($mediaCaptions[$i] ?? '') ?: $media['media_field_1'];
            $vars["alt{$fx}"] = ($mediaAlts[$i] ?? '') ?: $media['media_field_3'];
            if (!empty($media['media_field_4'])) {
                $vars["text{$fx}"] = $media['media_field_4'];
            }
            if (MediaHelper::isImageFile($type) || MediaHelper::isSvgFile($type)) {
                $vars += $this->renderImage($tpl, $i, $path, $media, $vars, $fx, $rootBlock, $mediaSizes, $mediaLarges, $mediaLinks);
            } elseif (MediaHelper::isFile($type)) {
                $vars += $this->renderFile($mid, $i, $path, $media, $vars, $fx, $mediaUseIcons);
            }
            $vars['attr'] = $mediaAttr;
            $tpl->add(array_merge([
                'type' . $fx . '#' . $media['media_type'],
                "unit#{$actualType}"
            ], $rootBlock), $vars);
        }
        $varsRoot = $this->displaySizeStyleTrait($displaySize, $varsRoot);
        $varsRoot['align'] = $mediaAlign;
        $varsRoot['attr'] = $mediaAttr;
        $tpl->add(array_merge(["unit#{$actualType}"], $rootBlock), $varsRoot);
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
        $midAry = $this->explodeUnitData($this->getField1());
        $vars += ['type' => 'image'];
        $isMediaType = false;
        $eagerLoadedMedia = $this->getEagerLoadedMedia();

        foreach ($midAry as $i => $mid) {
            $mid = intval($mid);
            if (empty($i)) {
                $fx = '';
            } else {
                $fx = $i + 1;
            }
            if (isset($eagerLoadedMedia[$mid])) {
                $media = $eagerLoadedMedia[$mid];
            } else {
                $SQL = SQL::newSelect('media');
                $SQL->addWhereOpr('media_id', $mid);
                $media = Database::query($SQL->get(dsn()), 'row');
            }
            if (empty($media)) {
                $media = [
                    'media_type' => '',
                    'media_path' => '',
                    'media_image_size' => '',
                    'media_field_1' => '',
                    'media_field_2' => '',
                    'media_field_3' => '',
                    'media_field_4' => '',
                    'media_file_name' => '',
                    'media_thumbnail' => ''
                ];
            }
            $path = MediaHelper::urlencode($media['media_path']);
            if (isset($media['media_type']) && MediaHelper::isImageFile($media['media_type'])) {
                $isMediaType = true;
                $path .= MediaHelper::cacheBusting($media['media_update_date']);
            } elseif (isset($media['media_type']) && MediaHelper::isSvgFile($media['media_type'])) {
                $vars['type' . $fx] = 'svg';
                $path .= MediaHelper::cacheBusting($media['media_update_date']);
            } elseif ($media) {
                $vars['type' . $fx] = 'file';
            }
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            $size = $media['media_image_size'];
            $sizes = explode(' x ', $size);
            $landscape = 'true';
            if (isset($sizes[0]) && isset($sizes[1])) {
                $landscape = $sizes[0] > $sizes[1] ? 'true' : 'false';
            }
            $vars += [
                "media_id{$fx}" => $mid,
                "caption{$fx}" => $media['media_field_1'],
                "link{$fx}" => $media['media_field_2'],
                "alt{$fx}" => $media['media_field_3'],
                "title{$fx}" => $media['media_field_4'],
                "type{$fx}" => $media['media_type'],
                "name{$fx}" => $media['media_file_name'],
                "path{$fx}" => $path,
                "tiny{$fx}" => otherSizeImagePath($path, 'tiny'),
                "landscape{$fx}" => $landscape,
                "media_pdf{$fx}" => 'no',
                "use_icon{$fx}" => 'false',
            ];
            if (!empty($ext)) {
                $vars["icon{$fx}"] = pathIcon($ext);
            }
            if (!empty($media['media_thumbnail'])) {
                $vars["thumbnail{$fx}"] = MediaHelper::getPdfThumbnail($media['media_thumbnail']);
                $vars["media_pdf{$fx}"] = 'yes';
                $this->formatMultiLangUnitData($this->getField5(), $vars, 'use_icon');
            }
        }
        $this->formatMultiLangUnitData($this->getField4(), $vars, 'enlarged');
        $this->formatMultiLangUnitData($this->getField7(), $vars, 'override-link');
        $this->formatMultiLangUnitData($this->getField2(), $vars, 'override-caption');
        $this->formatMultiLangUnitData($this->getField3(), $vars, 'override-alt');

        // size select
        $size = $this->getSize();
        $this->renderSizeSelectTrait($this->getUnitType(), $this->getUnitType(), $size, $tpl, $rootBlock);

        // primary image
        if ($isMediaType) {
            if ($primaryImageUnitId = $this->getPrimaryImageUnitId()) {
                $unitId = $this->getId();
                $vars['primaryImageId'] = $this->getTempId();
                if ($unitId && $primaryImageUnitId === $unitId) {
                    $vars['primaryImageChecked'] = config('attr_checked');
                }
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
            'media_id' => $this->getField1(),
            'caption' => $this->getField2(),
            'alt' => $this->getField3(),
            'enlarged' => $this->getField4(),
            'use_icon' => $this->getField5(),
            'display_size' => $this->getField6(),
            'link' => $this->getField7(),
        ];
    }

    /**
     * メディア画像の描画
     *
     * @param Template $tpl
     * @param int $index
     * @param string $path
     * @param array $media
     * @param array $vars
     * @param string $suffix
     * @param array $rootBlock
     * @param array $mediaSizes
     * @param array $mediaLarges
     * @param array $mediaLinks
     * @return array
     */
    protected function renderImage(Template $tpl, int $index, string $path, array $media, array $vars, string $suffix, array $rootBlock, array $mediaSizes, array $mediaLarges, array $mediaLinks)
    {
        $cacheBustingPath = $path . MediaHelper::cacheBusting($media['media_update_date']);
        $vars["path{$suffix}"] = MEDIA_LIBRARY_DIR . $cacheBustingPath;
        $size = $mediaSizes[$index] ?? '';
        $unitLink = $mediaLinks[$index] ?? '';
        $link = $unitLink ? $unitLink : $media['media_field_2'];
        $url = false;
        $eid = $this->getEntryId();
        $type = $media['media_type'];
        $actualType = $this->getUnitType();
        $originalX = 0;
        $originalY = 0;
        if (!MediaHelper::isSvgFile($type)) {
            $vars["image_utid{$suffix}"] = $this->getId();
        }
        if (strpos($media['media_image_size'], 'x') !== false) {
            list($tempX, $tempY) = explode('x', $media['media_image_size']);
            $originalX = intval(trim($tempX));
            $originalY = intval(trim($tempY));
        }
        if (strpos($size, 'x') !== false) {
            list($tempX, $tempY) = explode('x', $size);
            if (empty($originalX) || empty($originalY) || ($originalX >= $tempX && $originalY >= $tempY)) {
                $vars["x{$suffix}"] = $tempX;
                $vars["y{$suffix}"] = $tempY;
            } else {
                $vars["x{$suffix}"] = $originalX;
                $vars["y{$suffix}"] = $originalY;
            }
        } elseif ($originalX > 0 && $originalY > 0) {
            $tempX = $mediaSizes[$index] ?? '';
            $tempY = intval(intval($tempX) * ($originalY / $originalX));
            if (!empty($tempX) && !empty($tempY) && $originalX >= $tempX && $originalY >= $tempY) {
                $vars["x{$suffix}"] = $tempX;
                $vars["y{$suffix}"] = $tempY;
            } else {
                $vars["x{$suffix}"] = $originalX;
                $vars["y{$suffix}"] = $originalY;
            }
        } elseif (MediaHelper::isSvgFile($type)) {
            $vars["x{$suffix}"] = $mediaSizes[$index] ?? '';
            $vars["y{$suffix}"] = $vars["x{$suffix}"];
            $vars["svg_utid{$suffix}"] = $this->getId();

            $doc = new DOMDocument();
            if ($doc->loadXML(file_get_contents(urldecode(MEDIA_LIBRARY_DIR . $path)))) {
                $svg = $doc->getElementsByTagName('svg');
                $item = $svg->item(0);
                if ($item !== null) {
                    $svgWidth = intval($item->getAttribute('width'));
                    $svgHeight = intval($item->getAttribute('height'));
                    if (empty($svgWidth) || empty($svgHeight)) {
                        if ($viewBox = $item->getAttribute('viewBox')) {
                            $viewBox = explode(' ', $viewBox);
                            $svgWidth = intval($viewBox[2]);
                            $svgHeight = intval($viewBox[3]);
                        }
                    }
                    if ($svgWidth > 0 && $svgHeight > 0) {
                        $vars["y{$suffix}"] = intval(intval($vars["x{$suffix}"]) * ($svgHeight / $svgWidth));
                    }
                }
            }
        } else {
            $vars["x{$suffix}"] = $mediaSizes[$index] ?? '';
            $vars["y{$suffix}"] = '';
        }
        if ($size !== '') {
            // 画像サイズ指定がある場合のみ、画像リサイズ用の変数を出力
            $vars["resizeWidth{$suffix}"] = $vars["x{$suffix}"];
            $vars["resizeHeight{$suffix}"] = $vars["y{$suffix}"];
        }
        if ($link) {
            $url = setGlobalVars($link);
        } elseif (isset($mediaLarges[$index]) && $mediaLarges[$index] !== 'no') {
            $url = MediaHelper::getImagePermalink($cacheBustingPath);
        }
        if (!empty($url) && isset($mediaLarges[$index]) && $mediaLarges[$index] !== 'no') {
            $varsLink = [
                "url{$suffix}" => $url,
                "link_eid{$suffix}" => $eid,
            ];
            if (!$link) {
                $varsLink["viewer{$suffix}"] = str_replace(
                    '{unit_eid}',
                    strval($eid),
                    config('entry_body_image_viewer')
                );
            }
            $tpl->add(array_merge([
                "link{$suffix}#front",
                "type{$suffix}#" . $media['media_type'],
                "unit#{$actualType}",
            ], $rootBlock), $varsLink);
            $tpl->add(array_merge([
                "link{$suffix}#rear",
                "type{$suffix}#" . $media['media_type'],
                "unit#{$actualType}",
            ], $rootBlock));
        }
        return $vars;
    }

    /**
     * メディアファイルの描画
     *
     * @param int $mid
     * @param int $index
     * @param string $path
     * @param array $media
     * @param array $vars
     * @param string $suffix
     * @param array $mediaUseIcons
     * @return array
     */
    protected function renderFile(int $mid, int $index, string $path, array $media, array $vars, string $suffix, array $mediaUseIcons): array
    {
        if (empty($media['media_status'])) {
            $url = MediaHelper::getFileOldPermalink($path, false);
        } else {
            $url = MediaHelper::getFilePermalink($mid, false);
        }
        $icon = pathIcon($media['media_extension']);
        $vars += [
            "url{$suffix}" => $url,
            "icon{$suffix}" => $icon,
            "x{$suffix}" => 70,
            "y{$suffix}" => 81,
            "file_utid{$suffix}" => $this->getId(),
        ];
        if (config('file_icon_size') === 'dynamic') {
            $xy = Storage::getImageSize($icon);
            $vars["x{$suffix}"] = $xy[0] ?? 70;
            $vars["y{$suffix}"] = $xy[1] ?? 81;
        }
        if (!empty($media['media_thumbnail'])) {
            $vars["thumbnail{$suffix}"] = $media['media_thumbnail'];
            if (isset($mediaUseIcons[$index])) {
                $vars["use_icon{$suffix}"] = $mediaUseIcons[$index];
            }
        }
        return $vars;
    }
}
