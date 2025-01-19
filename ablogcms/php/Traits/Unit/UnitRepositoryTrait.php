<?php

namespace Acms\Traits\Unit;

use Acms\Services\Facades\Database;
use Acms\Services\Unit\Contracts\Model;
use SQL;

trait UnitRepositoryTrait
{
    /**
     * 指定したエントリーのユニット数をカウント
     *
     * @param int $eid
     * @param bool $publicOnly
     * @return int
     */
    public function countUnitsTrait(int $eid, bool $publicOnly = false): int
    {
        $sql = SQL::newSelect('column');
        $sql->addSelect('*', 'column_amount', null, 'COUNT');
        $sql->addWhereOpr('column_attr', 'acms-form', '<>');
        $sql->addWhereOpr('column_entry_id', $eid);

        return (int) Database::query($sql->get(dsn()), 'one');
    }

    /**
     * 指定したエントリーとソート番号のユニットを取得
     *
     * @param int $eid
     * @param int $sort
     * @return array|null
     */
    public function getUnitBySortTrait(int $eid, int $sort): ?array
    {
        $sql = SQL::newSelect('column');
        $sql->addSelect('column_id');
        $sql->addWhereOpr('column_sort', $sort);
        $sql->addWhereOpr('column_entry_id', $eid);
        $sql->addWhereOpr('column_attr', 'acms-form', '<>');
        $sql->setOrder('column_id', 'DESC');

        return Database::query($sql->get(dsn()), 'row');
    }

    /**
     * ユニットをロード
     *
     * @param int $unitId
     * @return null|array
     */
    public function loadUnitFromDBTrait(int $unitId): ?array
    {
        $sql = SQL::newSelect('column');
        $sql->addWhereOpr('column_id', $unitId);

        return Database::query($sql->get(dsn()), 'row');
    }

    /**
     * データベースからエントリーのユニットをロード
     *
     * @param int $eid
     * @param int|null $rvid
     * @param int|null $range
     * @return array
     */
    public function loadUnitsFromDBTrait(int $eid, ?int $rvid = null, ?int $range = null): array
    {
        if ($rvid) {
            $sql = SQL::newSelect('column_rev');
            $sql->addWhereOpr('column_rev_id', $rvid);
        } else {
            $sql = SQL::newSelect('column');
        }
        $sql->addWhereOpr('column_entry_id', $eid);
        $sql->addWhereOpr('column_attr', 'acms-form', '<>');
        $sql->setOrder('column_sort');
        if (!is_null($range)) {
            $sql->setLimit($range);
        }
        $q = $sql->get(dsn());

        return Database::query($q, 'all');
    }

    /**
     * 1ユニットの削除
     *
     * @param int $unitId
     * @param int|null $rvid
     * @return void
     */
    public function removeUnitTrait(int $unitId, ?int $rvid = null): void
    {
        $isRevision = $rvid && $rvid > 0;
        $tableName = $isRevision ? 'column_rev' : 'column';
        $sql = SQL::newDelete($tableName);
        $sql->addWhereOpr('column_id', $unitId);
        if ($isRevision) {
            $sql->addWhereOpr('column_rev_id', $rvid);
        }
        Database::query($sql->get(dsn()), 'exec');
    }

    /**
     * 全ユニットを削除
     *
     * @param int $eid
     * @param int|null $rvid
     * @return void
     */
    public function removeUnitsTrait(int $eid, ?int $rvid = null): void
    {
        $isRevision = $rvid && $rvid > 0;
        $tableName = $isRevision ? 'column_rev' : 'column';
        $sql = SQL::newDelete($tableName);
        $sql->addWhereOpr('column_entry_id', $eid);
        $sql->addWhereOpr('column_attr', 'acms-form', '<>');
        if ($isRevision) {
            $sql->addWhereOpr('column_rev_id', $rvid);
        }
        Database::query($sql->get(dsn()), 'exec');
    }

    /**
     * 指定したエントリーのユニットに存在するリビジョンIDを取得
     *
     * @param int $eid
     * @return int[]
     */
    public function getRevisionIds(int $eid): array
    {
        $sql = SQL::newSelect('column_rev');
        $sql->setSelect('column_rev_id', 'rvid', null, 'DISTINCT');
        $sql->addWhereOpr('column_entry_id', $eid);

        return Database::query($sql->get(dsn()), 'list');
    }

    /**
     * 指定した順番以降のユニット並び番号を更新する
     *
     * @param int $sort
     * @param int $eid
     * @param int|null $rvid
     * @return void
     */
    public function formatOrderWithInsertionTrait(int $sort, int $eid, ?int $rvid = null): void
    {
        if ($rvid) {
            $sql = SQL::newUpdate('column_rev');
            $sql->addWhereOpr('column_rev_id', $rvid);
        } else {
            $sql = SQL::newUpdate('column');
        }
        $sql->addUpdate('column_sort', SQL::newField('column_sort + 1'));
        $sql->addWhereOpr('column_sort', $sort, '>');
        $sql->addWhereOpr('column_entry_id', $eid);

        Database::query($sql->get(dsn()), 'exec');
    }

    /**
     * 保存時に1つのユニットから複数ユニットに増加できるユニットの処理
     *
     * @param \Acms\Services\Unit\Contracts\Model $model
     * @param ?int $summaryRange
     * @param int $overCount
     * @return array
     */
    protected function handleMultipleUnitsTrait(Model $model, ?int &$summaryRange, int &$overCount): array
    {
        $items = [];
        $baseSortNum = $model->getSort();
        $id = $model->getTempId();

        if ($model->getUnitType() === 'media') {
            // メディアユニットの場合
            $captions = $_POST["media_caption_{$id}"] ?? '';
            if (is_array($captions)) {
                // 多言語ユニットの場合
                return [$model];
            } else {
                // 通常メディアユニットの場合
                $mediaIds = $_POST["media_id_{$id}"] ?? [];
                if (!is_array($mediaIds)) {
                    $mediaIds = [$mediaIds];
                }
                foreach ($mediaIds as $j => $mid) {
                    if ($baseSortNum <= $summaryRange && $j > 0) {
                        $summaryRange++;
                    }
                    $newModel = clone $model;
                    $newModel->setSort($baseSortNum + $j);
                    if ($j > 0) {
                        $overCount++;
                        $newModel->setId(0);
                        $newModel->setTempId(uniqueString());
                    }
                    $newId = $newModel->getTempId();
                    $_POST["media_id_{$newId}"] = $mid;
                    $items[] = $newModel;
                }
            }
        } elseif ($model->getUnitType() === 'image') {
            // 画像ユニットの場合
            $captions = $_POST["image_caption_{$id}"] ?? '';
            if (is_array($captions)) {
                // 多言語ユニットの場合
                return [$model];
            } else {
                // 通常画像ユニットの場合
                $imageFiles = $_POST["image_file_{$id}"] ?? [];
                $tmpFiles = $_FILES["image_file_{$id}"]['tmp_name'] ?? [];
                $exifAry = $_POST["image_exif_{$id}"] ?? [];
                $old = $_POST["image_old_{$id}"] ?? null;
                $oldSize = $_POST["old_image_size_{$id}"] ?? '';
                $imageSize = $_POST["image_size_{$id}"] ?? '';
                $imageEdit = $_POST["image_edit_{$id}"] ?? '';
                $imageCaption = $_POST["image_caption_{$id}"] ?? '';
                $imageLink = $_POST["image_link_{$id}"] ?? '';
                $imageAlt = $_POST["image_alt_{$id}"] ?? '';

                if (empty($imageFiles)) {
                    return [$model];
                }
                if (!is_array($imageFiles)) {
                    $imageFiles = [$imageFiles];
                }
                if (!is_array($tmpFiles)) {
                    $tmpFiles = [$tmpFiles];
                }
                if (!is_array($exifAry)) {
                    $exifAry = [$exifAry];
                }
                foreach ($imageFiles as $j => $file) {
                    if ($baseSortNum <= $summaryRange && $j > 0) {
                        $summaryRange++;
                    }
                    $newModel = clone $model;
                    $newModel->setSort($baseSortNum + $j);
                    if ($j > 0) {
                        $overCount++;
                        $newModel->setId(0);
                        $newModel->setTempId(uniqueString());
                    }
                    $newId = $newModel->getTempId();
                    $_POST["image_file_{$newId}"] = $file;
                    $_POST["image_exif_{$newId}"] = array_shift($exifAry);
                    $_FILES["image_file_{$newId}"]['tmp_name'] = array_shift($tmpFiles);
                    $_POST["image_old_{$newId}"] = $old;
                    $_POST["old_image_size_{$newId}"] = $oldSize;
                    $_POST["image_size_{$newId}"] = $imageSize;
                    $_POST["image_edit_{$newId}"] = $imageEdit;
                    $_POST["image_caption_{$newId}"] = $imageCaption;
                    $_POST["image_link_{$newId}"] = $imageLink;
                    $_POST["image_alt_{$newId}"] =  $imageAlt;
                    $items[] = $newModel;
                }
            }
        } else {
            // 一般ユニット
            $items[] = $model;
        }
        return $items;
    }
}
