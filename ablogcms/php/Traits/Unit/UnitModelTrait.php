<?php

namespace Acms\Traits\Unit;

use Acms\Services\Facades\Database;
use Acms\Services\Unit\Contracts\Model;
use SQL;

trait UnitModelTrait
{
    /**
     * 新しいユニットIDを発行
     *
     * @return int
     */
    public function generateNewIdTrait(): int
    {
        return (int) Database::query(SQL::nextval('column_id', dsn()), 'seq');
    }

    /**
     * ユニットをデータベースに保存
     *
     * @param \Acms\Services\Unit\Contracts\Model $model
     * @param bool $isRevision
     * @return void
     */
    public function insertDataTrait(Model $model, bool $isRevision): void
    {
        $tableName = $isRevision ? 'column_rev' : 'column';

        $sql = SQL::newInsert($tableName);
        $sql->addInsert('column_id', $model->getId());
        $sql->addInsert('column_sort', $model->getSort());
        $sql->addInsert('column_entry_id', $model->getEntryId());
        $sql->addInsert('column_blog_id', $model->getBlogId());
        if ($isRevision) {
            $sql->addInsert('column_rev_id', $model->getRevId());
        }
        $sql->addInsert('column_type', $model->getType());
        $sql->addInsert('column_align', $model->getAlign());
        $sql->addInsert('column_attr', $model->getAttr());
        $sql->addInsert('column_group', $model->getGroup());
        $sql->addInsert('column_size', $model->getSize());
        $sql->addInsert('column_field_1', $model->getField1());
        $sql->addInsert('column_field_2', $model->getField2());
        $sql->addInsert('column_field_3', $model->getField3());
        $sql->addInsert('column_field_4', $model->getField4());
        $sql->addInsert('column_field_5', $model->getField5());
        $sql->addInsert('column_field_6', $model->getField6());
        $sql->addInsert('column_field_7', $model->getField7());
        $sql->addInsert('column_field_8', $model->getField8());

        Database::query($sql->get(dsn()), 'exec');
    }

    /**
     * ユニットのソート番号を更新
     *
     * @param int $sort
     * @param int $eid
     * @param int $bid
     * @param bool $isRevision
     * @param int|null $rvid
     * @return void
     */
    protected function updateSortNumberTrait(int $sort, int $eid, int $bid, bool $isRevision, ?int $rvid = null): void
    {
        $tableName = $isRevision ? 'column_rev' : 'column';
        $sql = SQL::newSelect($tableName);
        $sql->setSelect('column_id');
        $sql->addWhereOpr('column_sort', $sort);
        $sql->addWhereOpr('column_entry_id', $eid);
        $sql->addWhereOpr('column_blog_id', $bid);
        if ($isRevision) {
            $sql->addWhereOpr('column_rev_id', $rvid);
        }
        if (Database::query($sql->get(dsn()), 'one')) {
            $sql = SQL::newUpdate($tableName);
            $sql->setUpdate('column_sort', SQL::newOpr('column_sort', 1, '+'));
            $sql->addWhereOpr('column_sort', $sort, '>=');
            $sql->addWhereOpr('column_entry_id', $eid);
            $sql->addWhereOpr('column_blog_id', $bid);
            if ($rvid) {
                $sql->addWhereOpr('column_rev_id', $rvid);
            }
            Database::query($sql->get(dsn()), 'exec');
        }
    }
}
