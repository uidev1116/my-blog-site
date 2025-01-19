<?php

namespace Acms\Services\Unit;

use Acms\Services\Unit\Contracts\Model;
use Acms\Services\Facades\Entry;
use Acms\Services\Facades\Database;
use Acms\Services\Facades\Media;
use SQL;

class Repository
{
    use \Acms\Traits\Unit\UnitRepositoryTrait;

    /**
     * ユニットレジストリ
     * @var \Acms\Services\Unit\Registry
     */
    protected $unitRegistory;

    /**
     * コンストラクタ
     *
     * @param \Acms\Services\Unit\Registry $resistry
     */
    public function __construct(Registry $resistry)
    {
        $this->unitRegistory = $resistry;
    }

    /**
     * ユニットモデルを作成
     *
     * @param string $type
     * @return null|Model
     */
    public function makeModel(string $type): ?Model
    {
        $baseType = detectUnitTypeSpecifier($type); // 特定指定子を除外した、一般名のユニット種別
        if (!$this->unitRegistory->exists($baseType)) {
            return null;
        }
        $model = $this->unitRegistory->make($baseType); // ユニットモデルを生成
        $model->setType($type);

        return $model;
    }

    /**
     * ユニットモデルをDBデータから生成
     *
     * @param array $unit
     * @return Model|null
     */
    public function loadModel(array $unit): ?Model
    {
        if ($model = $this->makeModel($unit['column_type'])) {
            $model->load($unit); // モデルにデータをロード
            return $model;
        }
        return null;
    }

    /**
     * ユニットモデル（配列）をDBデータから生成
     *
     * @param array $units
     * @return array
     */
    public function loadModels(array $units): array
    {
        $models = array_map(function ($unit) {
            return $this->loadModel($unit);
        }, $units);

        return array_filter($models, function ($model) {
            return $model !== null;
        });
    }

    /**
     * 追加時のユニットを作成
     *
     * @param string $type
     * @param string $addType
     * @param int $configIndex
     * @return Model|null
     */
    public function create(string $type, string $addType, int $configIndex = 0): ?Model
    {
        if ($model = $this->makeModel($type)) {
            $model->create($addType, $configIndex); // 追加時のユニットを新規生成
            return $model;
        }
        return null;
    }

    /**
     * 初期表示のユニットを作成
     *
     * @param string $type
     * @param int $configIndex
     * @return Model|null
     */
    public function createDefault(string $type, int $configIndex = 0): ?Model
    {
        if ($model = $this->makeModel($type)) {
            $model->createDefault($configIndex); // 初期表示のユニットを新規生成
            return $model;
        }
        return null;
    }

    /**
     * 指定したユニットIDのモデルをロード
     *
     * @param int $utid
     * @return Model|null
     */
    public function loadUnit(int $utid): ?Model
    {
        $unit = $this->loadUnitFromDBTrait($utid);
        if (empty($unit)) {
            return null;
        }
        if ($model = $this->makeModel($unit['column_type'])) {
            $model->load($unit); // モデルにデータをロード
            return $model;
        }
        return null;
    }

    /**
     * 指定したエントリーからユニットをロード
     *
     * @param int $eid
     * @param ?int $rvid
     * @param ?int $range
     * @return Model[]
     */
    public function loadUnits(int $eid, ?int $rvid = null, ?int $range = null): array
    {
        $units = $this->loadUnitsFromDBTrait($eid, $rvid, $range);
        return $this->loadModels($units);
    }

    /**
     * 新規追加ユニットをロード
     *
     * @param string $addType
     * @return array
     */
    public function loadAddUnit(string $addType): array
    {
        $addUnitTypes = configArray('column_def_add_' . $addType . '_type');
        if (empty($addUnitTypes)) {
            return [];
        }
        $units = [];
        foreach ($addUnitTypes as $i => $unitType) {
            if ($model = $this->create($unitType, $addType, $i)) {
                $units[] = $model;
            }
        }
        return $units;
    }

    /**
     * 初期表示ユニットをロード
     *
     * @return array
     */
    public function loadDefaultUnit(): array
    {
        $defaultUnitTypes = configArray('column_def_insert_type');
        if (empty($defaultUnitTypes)) {
            return [];
        }
        $units = [];
        foreach ($defaultUnitTypes as $i => $type) {
            if ($model = $this->createDefault($type, $i)) {
                $units[] = $model;
            }
        }
        return $units;
    }

    /**
     * ユニットデータをEagerLoad
     *
     * @param int[] $entryIds
     * @return array
     */
    public function eagerLoadUnits(array $entryIds): array
    {
        if (empty($entryIds)) {
            return [];
        }
        $sql = SQL::newSelect('column');
        $sql->addWhereIn('column_entry_id', array_unique($entryIds));
        $sql->addWhereOpr('column_attr', 'acms-form', '<>');
        $sql->addOrder('column_sort', 'ASC');
        $q = $sql->get(dsn());
        $unitsData = Database::query($q, 'all');

        $eagerLoadingData = $this->formatEagerLoadUnits($unitsData, function ($carry, $unit) {
            $eid = $unit->getEntryId();
            if (!isset($carry[$eid])) {
                $carry[$eid] = [];
            }
            $carry[$eid][] = $unit;
            return $carry;
        }, []);

        return $eagerLoadingData;
    }

    /**
     * メイン画像ユニットをEagerLoad
     *
     * @param array $entries
     * @return array {unit: Model[], media: []}
     */
    public function eagerLoadPrimaryImageUnits(array $entries): array
    {
        $eagerLoadingData = [
            'unit' => [],
            'media' => [],
        ];
        $mainImageUnits = array_reduce($entries, function ($carry, $entry) {
            if ($primaryImageUnitId = (int) $entry['entry_primary_image']) {
                $carry[] = $primaryImageUnitId;
            }
            return $carry;
        }, []);
        if (empty($mainImageUnits)) {
            return $eagerLoadingData;
        }
        $sql = SQL::newSelect('column');
        $sql->addWhereIn('column_id', array_unique($mainImageUnits));
        $sql->addWhereOpr('column_attr', 'acms-form', '<>');
        $q = $sql->get(dsn());
        $unitsData = Database::query($q, 'all');

        $eagerLoadingData['unit'] = $this->formatEagerLoadUnits($unitsData, function ($carry, $unit) {
            $utid = $unit->getId();
            $carry[$utid] = $unit;
            return $carry;
        }, []);
        $eagerLoadingData['media'] = Media::mediaEagerLoadFromUnit($eagerLoadingData['unit']);

        return $eagerLoadingData;
    }

    /**
     * ユニットをEagerLoad
     *
     * @param array $unitsData
     * @return array {unit: Model[], media: []}
     */
    public function formatEagerLoadUnits(array $unitsData, callable $reduce, $initData)
    {
        foreach ($unitsData as $data) {
            if ($model = $this->makeModel($data['column_type'])) {
                $model->load($data); // モデルにデータをロード
                $initData = $reduce($initData, $model);
            }
        }
        return $initData;
    }

    /**
     * POSTデータからユニットを抽出
     *
     * @param ?int $summaryRange
     * @param bool $removeOld
     * @param bool $isDirectEdit
     * @return array
     */
    public function extractUnits(?int $summaryRange, bool $removeOld = true, bool $isDirectEdit = false): array
    {
        if (!empty($_POST['column_object'])) {
            return unserialize(gzinflate(base64_decode($_POST['column_object']))); // @phpstan-ignore-line
        }
        $units = [];
        $overCount = 0;
        $types = $_POST['type'] ?? null;
        if (empty($types) || !is_array($types)) {
            return $units;
        }
        foreach ($types as $i => $type) {
            $id = $_POST['id'][$i];
            $model = $this->makeModel($type);
            if (empty($model)) {
                continue;
            }
            $model->setTempId($id);
            $model->setId((int) ($_POST['clid'][$i] ?? 0));
            $model->setAlign($_POST['align'][$i] ?? '');
            $model->setSort((int) ($_POST['sort'][$i] ?? 0) + $overCount);
            $model->setAttr($_POST['attr'][$i] ?? '');
            $model->setGroup($_POST['group'][$i] ?? '');
            $model->setSize('');
            $models = $this->handleMultipleUnitsTrait($model, $summaryRange, $overCount);
            foreach ($models as $model2) {
                $model2->extract($_POST, $removeOld, $isDirectEdit);
                $units[] = $model2;
            }
        }
        Entry::setSummaryRange($summaryRange);
        return $units;
    }

    /**
     * ユニットを保存
     *
     * @param \Acms\Services\Unit\Contracts\Model[] $units
     * @param int $eid
     * @param int $bid
     * @param bool $add
     * @param ?int $rvid
     * @return array
     */
    public function saveUnits(array $units, int $eid, int $bid, bool $add = false, ?int $rvid = null): array
    {
        $isRevision = false;
        if (enableRevision() && $rvid !== null) {
            $isRevision = true;
        }
        $imageUnitIdTable = [];
        $offset = 0;
        if (!$add) {
            $this->removeUnitsTrait($eid, $rvid);
            $arySort = array_map(function ($unit) {
                return $unit->getSort();
            }, $units);
            if (!empty($arySort)) {
                $offset = min($arySort) - 1;
            }
        }
        foreach ($units as $unit) {
            if ($unit->canSave()) {
                $unitId = $unit->getId();
                if ($unitId && $unitId > 0) {
                    $this->removeUnitTrait($unitId, $rvid); // 既存ユニットデータを削除
                }
                $clid = $unit->save($eid, $bid, $isRevision, $rvid, $offset);
                if ($unit->getIsImageUnit()) {
                    $imageUnitIdTable[$unit->getTempId()] = $clid;
                }
            } else {
                $offset++;
            }
        }
        return $imageUnitIdTable;
    }

    /**
     * リビジョンユニットを保存
     *
     * @param array $units
     * @param int $eid
     * @param int $bid
     * @param int|null $rvid
     * @return array|null
     */
    public function saveRevisionUnits(array $units, int $eid, int $bid, ?int $rvid = null): ?array
    {
        if (!enableRevision()) {
            return null;
        }
        return $this->saveUnits($units, $eid, $bid, false, $rvid);
    }

    /**
     * 指定したエントリーのユニットを複製
     *
     * @param int $eid
     * @param int $newEid
     * @peram int|null $rvid
     * @return array
     */
    public function duplicateUnits(int $eid, int $newEid, ?int $rvid = null): array
    {
        /** @var \Acms\Services\Unit\Contracts\Model[] $units */
        $units = $this->loadUnits($eid, $rvid);
        $isRevision = $rvid && $rvid > 0;
        $idMappingTable = [];

        foreach ($units as $unit) {
            $id = $unit->getId();
            $newId = $unit->generateNewIdTrait();
            $idMappingTable[$id] = $newId;
            $unit->setId($newId);
            $unit->setEntryId($newEid);
            if ($isRevision) {
                $unit->setRevId($rvid);
            }
            $unit->handleDuplicate();
            $unit->insertDataTrait($unit, $isRevision);
        }
        return $idMappingTable;
    }

    /**
     * リビジョンユニットを複製して別リビジョンに複製
     * @param int $eid
     * @param int $sourceRvid
     * @param int $targetRvid
     * @return void
     */
    public function duplicateRevisionUnits(int $eid, int $sourceRvid, int $targetRvid): void
    {
        /** @var \Acms\Services\Unit\Contracts\Model[] $units */
        $units = $this->loadUnits($eid, $sourceRvid);

        foreach ($units as $unit) {
            $unit->setRevId($targetRvid);
            $unit->handleDuplicate();
            $unit->insertDataTrait($unit, true);
        }
    }

    /**
     * 指定したユニットを同エントリー内に複製
     *
     * @param int $unitId
     * @param int $eid
     * @param int|null $rvid
     * @return \Acms\Services\Unit\Contracts\Model
     */
    public function duplicateUnit(int $unitId, int $eid, ?int $rvid = null): Model
    {
        $unit = $this->loadUnit($unitId);
        if (!$unit instanceof Model) {
            throw new \RuntimeException("The unit with ID={$unitId} was not found.");
        }
        $sort = $unit->getSort();
        $this->formatOrderWithInsertionTrait($sort, $eid, $rvid);

        $isRevision = $rvid && $rvid > 0;
        $newId = $unit->generateNewIdTrait();
        $unit->setId($newId);
        $unit->setSort($sort + 1);
        $unit->handleDuplicate();
        $unit->insertDataTrait($unit, $isRevision);

        return $unit;
    }

    /**
     * 1ユニット削除
     *
     * @param int $unitId
     * @param int $eid
     * @param int|null $rvid
     * @param bool $withAssets
     * @return \Acms\Services\Unit\Contracts\Model
     */
    public function removeUnit(int $unitId, int $eid, ?int $rvid = null, bool $withAssets = true): Model
    {
        $unit = $this->loadUnit($unitId);
        if (!$unit instanceof Model) {
            throw new \RuntimeException("The unit with ID={$unitId} was not found.");
        }
        if ($withAssets) {
            $unit->handleRemove();
        }
        $this->removeUnitTrait($unitId, $rvid);

        return $unit;
    }

    /**
     * 全ユニットを削除
     *
     * @param int $eid
     * @param int|null $rvid
     * @param bool $withAssets
     * @return \Acms\Services\Unit\Contracts\Model[]
     */
    public function removeUnits(int $eid, ?int $rvid = null, bool $withAssets = true): array
    {
        /** @var \Acms\Services\Unit\Contracts\Model[] $units */
        $units = $this->loadUnits($eid, $rvid);
        if ($withAssets) {
            foreach ($units as $unit) {
                $unit->handleRemove();
            }
        }
        $this->removeUnitsTrait($eid, $rvid);

        return $units;
    }

    /**
     * ユニットの検索テキストを取得
     *
     * @param int $eid
     * @return string
     */
    public function getUnitSearchText(int $eid): string
    {
        /** @var \Acms\Services\Unit\Contracts\Model[] $units */
        $units = $this->loadUnits($eid);
        return array_reduce($units, function ($carry, $unit) {
            if ($unit->getAlign() !== 'hidden') {
                if ($unitSummaryText = $unit->getSearchText()) {
                    $carry .= "{$unitSummaryText} ";
                }
            }
            return $carry;
        }, '');
    }
}
