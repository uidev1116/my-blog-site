<?php

namespace Acms\Services\Cache\Adapters\Custom;

use Symfony\Component\Cache\Adapter\AbstractTagAwareAdapter;
use Symfony\Component\Cache\PruneableInterface;
use Symfony\Component\Cache\Marshaller\DefaultMarshaller;
use DB;
use SQL;

class DatabaseTagAwareAdapter extends AbstractTagAwareAdapter implements PruneableInterface
{
    /**
     * @var \Symfony\Component\Cache\Marshaller\DefaultMarshaller
     */
    private $marshaller;

    /**
     * @var string
     */
    private $namespace = '';

    /**
     * キャッシュテーブル名
     *
     * @var string
     */
    protected $cacheTableName = 'cache_data';

    /**
     * キャッシュテーブルのキーカラム名
     *
     * @var string
     */
    protected $cacheKeyColName = 'cache_data_key';

    /**
     * キャッシュテーブルのデータカラム名
     *
     * @var string
     */
    protected $cacheDataColName = 'cache_data_value';

    /**
     * キャッシュテーブルのライフタイムカラム名
     *
     * @var string
     */
    protected $cacheLifetimeColName = 'cache_data_lifetime';

    /**
     * キャッシュテーブルのタイムカラム名
     *
     * @var string
     */
    protected $cacheTimeColName = 'cache_data_time';

    /**
     * キャッシュタグのテーブル名
     *
     * @var string
     */
    protected $cacheTagTableName = 'cache_tag';

    /**
     * キャッシュタグのタグカラム名
     *
     * @var string
     */
    protected $cacheTagNameColName = 'cache_tag_name';

    /**
     * キャッシュタグのキーカラム名
     *
     * @var string
     */
    protected $cacheTagKeyColNmae = 'cache_tag_key';

    /**
     * Constructor
     *
     * @param mixed $connOrDsn
     * @param string $namespace
     * @param int $defaultLifetime
     * @return void
     */
    public function __construct(string $namespace = '', int $defaultLifetime = 0)
    {
        $this->marshaller = new DefaultMarshaller();
        $this->namespace = $namespace;

        parent::__construct($namespace, $defaultLifetime);
    }

    /**
     * 有効期限切れのキャッシュを削除
     *
     * @return bool
     */
    public function prune(): bool
    {
        $sql = SQL::newSelect($this->cacheTableName);
        $sql->setSelect($this->cacheKeyColName);
        $sql->addWhereOpr($this->cacheLifetimeColName, REQUEST_TIME, '<=');
        if ($this->namespace !== '') {
            $sql->addWhereOpr($this->cacheKeyColName, $this->namespace . '%', 'LIKE');
        }
        $ids = DB::query($sql->get(dsn()), 'list');

        $sql = SQL::newDelete($this->cacheTableName);
        $sql->addWhereIn($this->cacheKeyColName, $ids);
        $result = !!DB::query($sql->get(dsn()), 'exec');

        $sql = SQL::newDelete($this->cacheTagTableName);
        $sql->addWhereIn($this->cacheTagKeyColNmae, $ids);
        DB::query($sql->get(dsn()), 'exec');

        return $result;
    }

    /**
     * 複数のキャッシュ・アイテムをフェッチします
     *
     * @param array $ids
     * @return array|\Traversable
     */
    protected function doFetch(array $ids)
    {
        $sql = SQL::newSelect($this->cacheTableName);
        $sql->addSelect($this->cacheKeyColName, '`key`');
        $case = SQL::newCase();
        $where = SQL::newWhere();
        $where->addWhere(SQL::newOpr($this->cacheLifetimeColName, null), 'OR');
        $where->addWhere(SQL::newOpr($this->cacheLifetimeColName, REQUEST_TIME, '>'), 'OR');
        $case->add($where, SQL::newField($this->cacheDataColName));
        $case->setElse('expired');
        $sql->addSelect($case->get(dsn()), '`data`');
        $sql->addWhereIn($this->cacheKeyColName, $ids);

        $expired = [];
        $result = DB::query($sql->get(dsn()), 'all');
        foreach ($result as $row) {
            if ($row['data'] === 'expired') {
                $expired[] = $row['key'];
            } else {
                yield $row['key'] => $this->marshaller->unmarshall(\is_resource($row['data']) ? stream_get_contents($row['data']) : $row['data']);
            }
        }
        if (count($expired) > 0) {
            $sql = SQL::newDelete($this->cacheTableName);
            $sql->addWhereIn($this->cacheKeyColName, $expired);
            DB::query($sql->get(dsn()), 'exec');
        }
    }

    /**
     * キャッシュを持っていくるどうかを確認します
     *
     * @param string $id
     *
     * @return bool
     */
    protected function doHave(string $id)
    {
        $sql = SQL::newSelect($this->cacheTableName);
        $sql->addSelect($this->cacheKeyColName);
        $sql->addWhereOpr($this->cacheKeyColName, $id);
        $sql->addWhereOpr($this->cacheLifetimeColName, REQUEST_TIME, '>');

        return !!DB::query($sql->get(dsn()), 'one');
    }

    /**
     * プール内のすべてのアイテムを削除する。
     *
     * @param string $namespace
     * @return bool
     */
    protected function doClear(string $namespace)
    {
        if ('' === $namespace) {
            $q = "TRUNCATE TABLE $this->cacheTableName";
            $q2 = "TRUNCATE TABLE $this->cacheTagTableName";
        } else {
            $sql = SQL::newDelete($this->cacheTableName);
            $sql->addWhereOpr($this->cacheKeyColName, $this->namespace . '%', 'LIKE');
            $q = $sql->get(dsn());

            $sql = SQL::newDelete($this->cacheTagTableName);
            $sql->addWhereOpr($this->cacheTagKeyColNmae, $this->namespace . '%', 'LIKE');
            $q2 = $sql->get(dsn());
        }
        $result = !!DB::query($q, 'exec');
        DB::query($q2, 'exec');

        return $result;
    }

    /**
     * 複数のキャッシュ・アイテムを直ちに永続化する
     *
     * @param array $values
     * @param int $lifetime
     * @param array[] $addTagData
     * @param array[] $removeTagData
     * @return array キャッシュに失敗した識別子
     */
    protected function doSave(array $values, int $lifetime, array $addTagData = [], array $removeTagData = []): array
    {
        $failed = [];
        if (!$values = $this->marshaller->marshall($values, $failed)) {
            return $failed;
        }
        $lifetime = $lifetime ?: null;
        foreach ($values as $key => $data) {
            $sql = SQL::newInsertOrUpdate($this->cacheTableName);
            $sql->addInsert($this->cacheKeyColName, $key);
            $sql->addInsert($this->cacheDataColName, $data);
            $sql->addInsert($this->cacheLifetimeColName, REQUEST_TIME + $lifetime);
            $sql->addInsert($this->cacheTimeColName, REQUEST_TIME);
            $sql->addUpdate($this->cacheDataColName, $data);
            $sql->addUpdate($this->cacheLifetimeColName, REQUEST_TIME + $lifetime);
            $sql->addUpdate($this->cacheTimeColName, REQUEST_TIME);
            DB::query($sql->get(dsn()), 'exec');
        }

        foreach ($addTagData as $tagId => $ids) {
            foreach ($ids as $id) {
                $sql = SQL::newInsertOrUpdate($this->cacheTagTableName);
                $sql->addInsert($this->cacheTagNameColName, $tagId);
                $sql->addInsert($this->cacheTagKeyColNmae, $id);
                $sql->addUpdate($this->cacheTagKeyColNmae, $id);
                DB::query($sql->get(dsn()), 'exec');
            }
        }

        foreach ($removeTagData as $tagId => $ids) {
            $sql = SQL::newDelete($this->cacheTagTableName);
            $sql->addWhereOpr($this->cacheTagNameColName, $tagId);
            $sql->addWhereIn($this->cacheTagKeyColNmae, $ids);
            DB::query($sql->get(dsn()), 'exec');
        }

        return $failed;
    }

    /**
     * プールから複数のアイテムと対応するタグを削除します
     *
     * @param array $ids
     * @return bool
     */
    protected function doDelete(array $ids): bool
    {
        $sql = SQL::newDelete($this->cacheTableName);
        $sql->addWhereIn($this->cacheKeyColName, $ids);

        return !!DB::query($sql->get(dsn()), 'exec');
    }

    /**
     * タグと削除されたアイテムの関係を削除します
     *
     * @param array $tagData Array of tag => key identifiers that should be removed from the pool
     * @return bool
     */
    protected function doDeleteTagRelations(array $tagData): bool
    {
        foreach ($tagData as $tagId => $idList) {
            $sql = SQL::newDelete($this->cacheTagTableName);
            $sql->addWhereOpr($this->cacheTagNameColName, $tagId);
            $sql->addWhereIn($this->cacheTagKeyColNmae, $idList);
            DB::query($sql->get(dsn()), 'exec');
        }
        return true;
    }

    /**
     * タグを使用してキャッシュされた項目を無効にします
     *
     * @param string[] $tagIds
     * @return bool
     */
    protected function doInvalidate(array $tagIds): bool
    {
        $sql = SQL::newSelect($this->cacheTagTableName);
        $sql->setSelect($this->cacheTagKeyColNmae);
        $sql->addWhereIn($this->cacheTagNameColName, $tagIds);
        $ids = DB::query($sql->get(dsn()), 'list');

        $sql = SQL::newDelete($this->cacheTableName);
        $sql->addWhereIn($this->cacheKeyColName, $ids);
        $result = !!DB::query($sql->get(dsn()), 'exec');

        $sql = SQL::newDelete($this->cacheTagTableName);
        $sql->addWhereIn($this->cacheTagKeyColNmae, $ids);
        DB::query($sql->get(dsn()), 'exec');

        return $result;
    }
}
