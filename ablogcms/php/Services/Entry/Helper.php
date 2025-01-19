<?php

namespace Acms\Services\Entry;

use Acms\Services\Facades\Common;
use Acms\Services\Facades\Entry;
use Acms\Services\Facades\Database as DB;
use Acms\Services\Facades\Logger as AcmsLogger;
use Acms\Services\Facades\Application;
use Acms\Services\Facades\Preview;
use SQL;
use ACMS_RAM;
use Field;

class Helper
{
    use \Acms\Traits\Common\AssetsTrait;

    /**
     * サマリーの表示で使うユニットの範囲を取得
     *
     * @var int
     */
    protected $summaryRange;

    /**
     * 苦肉の策で、新規アップロードされたファイルをここに一時保存する
     *
     * @var array
     */
    protected $uploadedFiles = [];

    /**
     * 苦肉の策で、新規バージョン作成か一時的に保存する
     *
     * @var mixed
     */
    protected $isNewVersion = false;

    /**
     * サマリーの表示で使うユニットの範囲を取得
     * extractUnits 後に決定
     *
     * @return int
     */
    public function getSummaryRange()
    {
        return $this->summaryRange;
    }

    /**
     * サマリーの表示で使うユニットの範囲を設定
     * extractUnits 時に設定
     * @param ?int $summaryRange
     * @return void
     */
    public function setSummaryRange(?int $summaryRange): void
    {
        $this->summaryRange = $summaryRange;
    }

    /**
     * アップロードされたファイルを取得
     * Entry::extractColumn 後に決定
     *
     * @return array
     */
    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    /**
     * アップロードされたファイルを取得
     * Entry::extractColumn 後に決定
     *
     * @param string $path
     * @return void
     */
    public function addUploadedFiles($path)
    {
        $this->uploadedFiles[] = $path;
    }

    /**
     * 新規バージョン作成の判定をセット
     *
     * @param boolean $flag
     * @return void
     */
    public function setNewVersion($flag)
    {
        $this->isNewVersion = $flag;
    }

    /**
     * 新規バージョン作成の判定を取得
     *
     * @return boolean
     */
    public function isNewVersion()
    {
        return $this->isNewVersion;
    }

    /**
     * エントリーコードの重複をチェック
     *
     * @param string $code
     * @param int $bid
     * @param int $cid
     * @param int $eid
     *
     * @return bool
     */
    public function validEntryCodeDouble($code, $bid = BID, $cid = null, $eid = null)
    {
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('entry');
        $SQL->addSelect('entry_id');
        $SQL->addWhereOpr('entry_code', $code);
        $SQL->addWhereOpr('entry_id', $eid, '<>');
        $SQL->addWhereOpr('entry_category_id', $cid);
        $SQL->addWhereOpr('entry_blog_id', $bid);

        if ($DB->query($SQL->get(dsn()), 'one')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * エントリーのタグをバリデート
     *
     * @param \Field_Validation $Entry
     *
     * @return \Field_Validation
     */
    public function validTag($Entry)
    {
        $tags = $Entry->get('tag');
        if (!empty($tags)) {
            $tags = Common::getTagsFromString($tags, false);
            foreach ($tags as $sort => $tag) {
                if (isReserved($tag)) {
                    $Entry->setMethod('tag', 'reserved', false);
                    break;
                }
                if (!preg_match(REGEX_INVALID_TAG_NAME, $tag)) {
                    $Entry->setMethod('tag', 'string', false);
                    break;
                }
            }
        }
        return $Entry;
    }

    /**
     * エントリーのサブカテゴリーをバリデート
     *
     * @param \Field_Validation $Entry
     *
     * @return \Field_Validation
     */
    public function validSubCategory($Entry)
    {
        $limit = config('entry_edit_sub_category_limit');
        if (is_numeric($limit)) {
            $subCategoryIds = $this->getSubCategoryFromString($Entry->get('sub_category_id'), ',');
            if (count($subCategoryIds) > intval($limit)) {
                $Entry->setMethod('sub_category_id', 'max_sub_category_id', false);
            }
        }
        return $Entry;
    }

    /**
     * メディアユニットの情報が欠落していないかバリデート
     *
     * @return bool
     */
    public function validateMediaUnit()
    {
        if (!isset($_POST['type']) || !is_array($_POST['type'])) {
            return true;
        }
        foreach ($_POST['type'] as $i => $type) {
            $id = $_POST['id'][$i];
            $type = detectUnitTypeSpecifier($type);
            if ($type === 'media') {
                if (!isset($_POST['media_id_' . $id])) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * PING送信
     *
     * @param string $endpoint
     * @param int $eid
     *
     * @return void
     */
    public function pingTrackback($endpoint, $eid)
    {
        $aryEndpoint = preg_split('@\s@', $endpoint, -1, PREG_SPLIT_NO_EMPTY);
        $title = ACMS_RAM::entryTitle($eid);
        $excerpt = mb_strimwidth(loadFulltext($eid), 0, 252, '...', 'UTF-8');
        $url = acmsLink([
            'bid'   => BID,
            'cid'   => ACMS_RAM::entryCategory($eid),
            'eid'   => $eid,
        ], false);
        $blog_name = ACMS_RAM::blogName(BID);

        if (empty($aryEndpoint)) {
            return;
        }

        foreach ($aryEndpoint as $ep) {
            try {
                $req = \Http::init($ep, 'POST');
                $req->setRequestHeaders([
                    'Content-Type: application/x-www-form-urlencoded'
                ]);
                $req->setPostData([
                    'title' => $title,
                    'excerpt' => $excerpt,
                    'url' => $url,
                    'blog_name' => $blog_name,
                ]);
                $response = $req->send();
                $response->getResponseBody();
            } catch (\Exception $e) {
                AcmsLogger::notice('トラックバックの送信に失敗しました', Common::exceptionArray($e, ['url' => $ep]));
            }
        }
    }

    /**
     * エントリーの削除
     *
     * @param int $eid
     *
     * @return void
     */
    public function entryDelete($eid, $changeRevision = false)
    {
        $unitRepository = Application::make('unit-repository');
        assert($unitRepository instanceof \Acms\Services\Unit\Repository);

        //------------
        // エントリ削除
        $sql = SQL::newDelete('entry');
        $sql->addWhereOpr('entry_id', $eid);
        DB::query($sql->get(dsn()), 'exec');
        ACMS_RAM::entry($eid, null);

        //-----------------
        // タグ・コメント削除
        foreach (['tag', 'comment'] as $tb) {
            $sql = SQL::newDelete($tb);
            $sql->addWhereOpr($tb . '_entry_id', $eid);
            DB::query($sql->get(dsn()), 'exec');
        }

        //------------------
        // 動的フォームを削除
        if ($changeRevision === false) {
            $sql = SQL::newDelete('column');
            $sql->addWhereOpr('column_entry_id', $eid);
            $sql->addWhereOpr('column_attr', 'acms-form');
            DB::query($sql->get(dsn()), 'exec');
        }

        //------------------
        // サブカテゴリーを削除
        $sql = SQL::newDelete('entry_sub_category');
        $sql->addWhereOpr('entry_sub_category_eid', $eid);
        DB::query($sql->get(dsn()), 'exec');

        //-----------------
        // 関連エントリを削除
        $sql = SQL::newDelete('relationship');
        $sql->addWhereOpr('relation_id', $eid);
        DB::query($sql->get(dsn()), 'exec');

        //-----------------
        // フルテキストを削除
        $sql = SQL::newDelete('fulltext');
        $sql->addWhereOpr('fulltext_eid', $eid);
        DB::query($sql->get(dsn()), 'exec');

        //-------------------------
        // ユニット削除・アセット類削除
        if ($changeRevision === false) {
            // カスタムフィールドのファイル類を削除
            $field = loadEntryField($eid);
            $this->removeFieldAssetsTrait($field);
            // ユニットを削除 & ユニットのファイル類を削除
            $unitRepository->removeUnits($eid, null, true);
        } else {
            // ユニットデータのみ削除
            $unitRepository->removeUnits($eid, null, false);
        }

        //------------------
        // フィールドデータ削除
        Common::saveField('eid', $eid);

        //-----------------------
        // キャッシュクリア予約削除
        Entry::deleteCacheControl($eid);
    }

    /**
     * エントリーのバージョンを削除
     *
     * @param int $eid
     *
     * @return void
     */
    public function revisionDelete($eid)
    {
        //------
        // unit
        $unitRepository = Application::make('unit-repository');
        assert($unitRepository instanceof \Acms\Services\Unit\Repository);

        $revisionIds = $unitRepository->getRevisionIds($eid);
        foreach ($revisionIds as $rvid) {
            if ($eid && $rvid) {
                $unitRepository->removeUnits($eid, $rvid, true);
            }
        }

        //-----
        // tag
        $SQL = SQL::newDelete('tag_rev');
        $SQL->addWhereOpr('tag_entry_id', $eid);
        DB::query($SQL->get(dsn()), 'exec');

        //---------------
        // sub category
        $SQL = SQL::newDelete('entry_sub_category_rev');
        $SQL->addWhereOpr('entry_sub_category_eid', $eid);
        DB::query($SQL->get(dsn()), 'exec');

        //-------
        // field
        $SQL = SQL::newSelect('entry_rev');
        $SQL->addSelect('entry_rev_id');
        $SQL->addWhereOpr('entry_id', $eid);
        if ($all = DB::query($SQL->get(dsn()), 'all')) {
            foreach ($all as $rev) {
                $rvid = $rev['entry_rev_id'];
                $field = loadEntryField($eid, $rvid);
                $this->removeFieldAssetsTrait($field);
                Common::saveField('eid', $eid, null, null, $rvid);
            }
        }

        //-------
        // entry
        $SQL = SQL::newDelete('entry_rev');
        $SQL->addWhereOpr('entry_id', $eid);
        DB::query($SQL->get(dsn()), 'exec');
    }

    /**
     * バージョンの切り替え
     *
     * @param int $rvid
     * @param int $eid
     * @param int $bid
     *
     * @return int|false
     */
    function changeRevision($rvid, $eid, $bid)
    {
        $DB = DB::singleton(dsn());
        $cid = null;
        $primaryImageId = null;
        if (!is_numeric($rvid)) {
            return false;
        }
        $sql = SQL::newSelect('entry_rev');
        $sql->addWhereOpr('entry_id', $eid);
        $sql->addWhereOpr('entry_rev_id', $rvid);
        $revision = DB::query($sql->get(dsn()), 'row');
        if (empty($revision)) {
            return false;
        }
        $publicDatetime = $revision['entry_start_datetime'];
        if (strtotime($publicDatetime) > REQUEST_TIME) {
            $sql = SQL::newUpdate('entry');
            $sql->setUpdate('entry_reserve_rev_id', $rvid);
            $sql->addWhereOpr('entry_id', $eid);
            DB::query($sql->get(dsn()), 'exec');
            return ACMS_RAM::entryCategory($eid);
        }

        // エントリの情報を削除
        Entry::entryDelete($eid, true);

        //-------
        // entry
        $SQL    = SQL::newSelect('entry_rev');
        $SQL->addWhereOpr('entry_id', $eid);
        $SQL->addWhereOpr('entry_rev_id', $rvid);
        $q      = $SQL->get(dsn());

        $Entry  = SQL::newInsert('entry');
        if ($row = $DB->query($q, 'row')) {
            $cid = $row['entry_category_id'];
            foreach ($row as $key => $val) {
                if (!preg_match('@^(entry_rev|entry_approval)@', $key)) {
                    $Entry->addInsert($key, $val);
                }
            }
            $Entry->addInsert('entry_current_rev_id', $rvid);
            $Entry->addInsert('entry_reserve_rev_id', 0);
            if (SUID) {
                $Entry->addInsert('entry_last_update_user_id', SUID);
            }
            $DB->query($Entry->get(dsn()), 'exec');

            $primaryImageId = $row['entry_primary_image'];
        }

        //------
        // unit
        $SQL    = SQL::newSelect('column_rev');
        $SQL->addWhereOpr('column_entry_id', $eid);
        $SQL->addWhereOpr('column_rev_id', $rvid);
        $q      = $SQL->get(dsn());

        $Unit   = SQl::newInsert('column');
        if ($DB->query($q, 'fetch') and ($row = $DB->fetch($q))) {
            do {
                foreach ($row as $key => $val) {
                    if ($key !== 'column_id' && $key !== 'column_rev_id') {
                        $Unit->addInsert($key, $val);
                    }
                }
                $nextUnitId = $DB->query(SQL::nextval('column_id', dsn()), 'seq');
                if (!empty($primaryImageId) && $row['column_id'] == $primaryImageId) {
                    $primaryImageId = $nextUnitId;
                }
                $Unit->addInsert('column_id', $nextUnitId);
                $DB->query($Unit->get(dsn()), 'exec');
            } while ($row = $DB->fetch($q));
        }

        //---------------------
        // primaryImageIdを更新
        $SQL = SQL::newUpdate('entry');
        $SQL->addUpdate('entry_primary_image', $primaryImageId);
        $SQL->addWhereOpr('entry_id', $eid);
        $DB->query($SQL->get(dsn()), 'exec');
        ACMS_RAM::entry($eid, null);

        //-------
        // field
        $Field = loadEntryField($eid, $rvid);
        Common::saveField('eid', $eid, $Field);

        //-------
        // tag
        $SQL    = SQL::newSelect('tag_rev');
        $SQL->addWhereOpr('tag_entry_id', $eid);
        $SQL->addWhereOpr('tag_rev_id', $rvid);
        $q      = $SQL->get(dsn());

        $Tag    = SQl::newInsert('tag');
        if ($DB->query($q, 'fetch') and ($row = $DB->fetch($q))) {
            do {
                foreach ($row as $key => $val) {
                    if ($key !== 'tag_rev_id') {
                        $Tag->addInsert($key, $val);
                    }
                }
                $DB->query($Tag->get(dsn()), 'exec');
            } while ($row = $DB->fetch($q));
        }

        //---------------
        // sub category
        $SQL = SQL::newDelete('entry_sub_category');
        $SQL->addWhereOpr('entry_sub_category_eid', $eid);
        $DB->query($SQL->get(dsn()), 'exec');

        $SQL = SQL::newSelect('entry_sub_category_rev');
        $SQL->addWhereOpr('entry_sub_category_eid', $eid);
        $SQL->addWhereOpr('entry_sub_category_rev_id', $rvid);
        $q = $SQL->get(dsn());
        $SubCategory = SQl::newInsert('entry_sub_category');
        if ($DB->query($q, 'fetch') and ($row = $DB->fetch($q))) {
            do {
                foreach ($row as $key => $val) {
                    if ($key !== 'entry_sub_category_rev_id') {
                        $SubCategory->addInsert($key, $val);
                    }
                }
                $DB->query($SubCategory->get(dsn()), 'exec');
            } while ($row = $DB->fetch($q));
        }

        //---------------
        // related entry
        $SQL    = SQL::newSelect('relationship_rev');
        $SQL->addWhereOpr('relation_id', $eid);
        $SQL->addWhereOpr('relation_rev_id', $rvid);

        $relations = $DB->query($SQL->get(dsn()), 'all');
        foreach ($relations as $relation) {
            $SQL    = SQL::newInsert('relationship');
            $SQL->addInsert('relation_id', $eid);
            $SQL->addInsert('relation_eid', $relation['relation_eid']);
            $SQL->addInsert('relation_type', $relation['relation_type']);
            $SQL->addInsert('relation_order', $relation['relation_order']);
            $DB->query($SQL->get(dsn()), 'exec');
        }

        //----------
        // fulltext
        Common::saveFulltext('eid', $eid, Common::loadEntryFulltext($eid));

        return $cid;
    }

    /**
     * サブカテゴリーを保存
     *
     * @param int $eid
     * @param int $masterCid
     * @param string $cids
     * @param int $bid
     * @param int|null $rvid
     */
    public function saveSubCategory($eid, $masterCid, $cids, $bid = BID, $rvid = null)
    {
        try {
            $DB = DB::singleton(dsn());
            $table = 'entry_sub_category';
            if (!empty($rvid)) {
                $table = 'entry_sub_category_rev';
            }
            $SQL = SQL::newDelete($table);
            $SQL->addWhereOpr('entry_sub_category_eid', $eid);
            if (!empty($rvid)) {
                $SQL->addWhereOpr('entry_sub_category_rev_id', $rvid);
            }
            $DB->query($SQL->get(dsn()), 'exec');

            $cidAry = $this->getSubCategoryFromString($cids, ',');
            foreach ($cidAry as $cid) {
                if ($masterCid == $cid) {
                    continue;
                }
                $SQL = SQL::newInsert($table);
                $SQL->addInsert('entry_sub_category_eid', $eid);
                $SQL->addInsert('entry_sub_category_id', $cid);
                $SQL->addInsert('entry_sub_category_blog_id', $bid);
                if (!empty($rvid)) {
                    $SQL->addInsert('entry_sub_category_rev_id', $rvid);
                }
                $DB->query($SQL->get(dsn()), 'exec');
            }
        } catch (\Exception $e) {
        }
    }

    /**
     * @param string $string
     * @param string $delimiter
     * @return array
     */
    public function getSubCategoryFromString($string, $delimiter = ',')
    {
        $cidAry = explode($delimiter, $string);
        $list = [];
        foreach ($cidAry as $item) {
            $item = preg_replace('/^[\s　]+|[\s　]+$/u', '', $item);
            if ($item !== '') {
                $list[] = $item;
            }
        }
        return $list;
    }

    /**
     * 関連エントリーを保存
     *
     * @param int $eid
     * @param array $entryAry
     * @param int $rvid
     * @param array $typeAry
     *
     * @return void
     */
    public function saveRelatedEntries($eid, $entryAry = [], $rvid = null, $typeAry = [], $loadedTypes = [])
    {
        $DB = DB::singleton(dsn());
        $table = 'relationship';
        if (!empty($rvid)) {
            $table = 'relationship_rev';
        }
        $SQL = SQL::newDelete($table);
        $SQL->addWhereOpr('relation_id', $eid);
        $SQL->addWhereIn('relation_type', $loadedTypes);
        if (!empty($rvid)) {
            $SQL->addWhereOpr('relation_rev_id', $rvid);
        }
        $DB->query($SQL->get(dsn()), 'exec');

        $exists = [];
        foreach ($entryAry as $i => $reid) {
            try {
                $type = $typeAry[$i] ?? '';
                if (!isset($exists[$type])) {
                    $exists[$type] = [];
                }
                if (in_array($reid, $exists[$type], true)) {
                    continue;
                }
                $SQL = SQL::newInsert($table);
                $SQL->addInsert('relation_id', $eid);
                $SQL->addInsert('relation_eid', $reid);
                $SQL->addInsert('relation_order', $i);
                if (!empty($type)) {
                    $SQL->addInsert('relation_type', $type);
                }
                if (!empty($rvid)) {
                    $SQL->addInsert('relation_rev_id', $rvid);
                }
                $DB->query($SQL->get(dsn()), 'exec');
                $exists[$type][] = $reid;
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * エントリーのバージョンを保存
     *
     * @param int $eid
     * @param int $rvid
     * @param array $entryAry
     * @param string $type
     * @param string $memo
     *
     * @return int|false
     */
    public function saveEntryRevision($eid, $rvid, $entryAry, $type = '', $memo = '')
    {
        if (!enableRevision()) {
            return false;
        }
        if (empty($rvid) || empty($type)) {
            $rvid = 1;
        }
        $isNewRevision = false;

        if ($type === 'new') {
            // 新しいリビジョン番号取得
            $sql = SQL::newSelect('entry_rev');
            $sql->addSelect('entry_rev_id', 'max_rev_id', null, 'MAX');
            $sql->addWhereOpr('entry_id', $eid);
            $sql->addWhereOpr('entry_blog_id', BID);

            $rvid = 2;
            if ($max = DB::query($sql->get(dsn()), 'one')) {
                $rvid = $max + 1;
            }
            if (empty($memo)) {
                $memo = sprintf(config('revision_default_memo'), $rvid);
            }
            $isNewRevision = true;
        } else {
            if ($rvid === 1) {
                $memo = config('revision_temp_memo');
            }
            $sql = SQL::newSelect('entry_rev');
            $sql->setSelect('entry_id');
            $sql->addWhereOpr('entry_id', $eid);
            $sql->addWhereOpr('entry_rev_id', $rvid);
            $isNewRevision = !DB::query($sql->get(dsn()), 'one');
        }

        $entryData = [];
        if ($isNewRevision) {
            // 現在のエントリ情報を抜き出す
            $sql = SQL::newSelect('entry');
            $sql->addWhereOpr('entry_id', $eid);
            $sql->addWhereOpr('entry_blog_id', BID);
            if ($row = DB::query($sql->get(dsn()), 'row')) {
                foreach ($row as $key => $val) {
                    $entryData[$key] = $val;
                }
            }
        }
        foreach ($entryAry as $key => $val) {
            $entryData[$key] = $val;
        }

        if ($isNewRevision) {
            // リビジョン作成
            $sql = SQL::newInsert('entry_rev');
            $sql->addInsert('entry_rev_id', $rvid);
            $sql->addInsert('entry_rev_user_id', SUID);
            $sql->addInsert('entry_rev_datetime', date('Y-m-d H:i:s', REQUEST_TIME));
            $sql->addInsert('entry_rev_memo', $memo);
            if (sessionWithApprovalAdministrator(BID, $entryData['entry_category_id'])) {
                $sql->addInsert('entry_rev_status', 'approved');
            }
            foreach ($entryData as $key => $val) {
                if (!in_array($key, ['entry_current_rev_id', 'entry_reserve_rev_id', 'entry_last_update_user_id'], true)) {
                    $sql->addInsert($key, $val);
                }
            }
            DB::query($sql->get(dsn()), 'exec');
        } else {
            $sql = SQL::newUpdate('entry_rev');
            $sql->addUpdate('entry_rev_datetime', date('Y-m-d H:i:s', REQUEST_TIME));
            if (!empty($memo)) {
                $sql->addUpdate('entry_rev_memo', $memo);
            }
            if (sessionWithApprovalAdministrator(BID, $entryData['entry_category_id'])) {
                $sql->addUpdate('entry_rev_status', 'approved');
            }
            $sql->addWhereOpr('entry_id', $eid);
            $sql->addWhereOpr('entry_rev_id', $rvid);
            foreach ($entryData as $key => $val) {
                if (!in_array($key, ['entry_current_rev_id', 'entry_last_update_user_id'], true)) {
                    $sql->addUpdate($key, $val);
                }
            }
            $sql->addUpdate('entry_blog_id', BID);
            DB::query($sql->get(dsn()), 'exec');
        }
        return $rvid;
    }

    /**
     * カスタムフィールドのバージョンを保存
     *
     * @param int $eid
     * @param Field $Field
     * @param int $rvid
     *
     * @return bool
     */
    public function saveFieldRevision($eid, $Field, $rvid)
    {
        if (!enableRevision()) {
            return false;
        }

        Common::saveField('eid', $eid, $Field, null, $rvid);

        return true;
    }

    /**
     * キャッシュ自動削除の情報を更新
     *
     * @param string $start
     * @param string $end
     * @param int $bid
     * @param int $eid
     *
     * @return bool
     */
    public function updateCacheControl($start, $end, $bid = BID, $eid = EID)
    {
        if (
            0
            || !$bid
            || !$eid
            || ACMS_RAM::entryStatus($eid) !== 'open'
        ) {
            return false;
        }

        $DB = DB::singleton(dsn());
        $SQL = SQL::newDelete('cache_reserve');
        $SQL->addWhereOpr('cache_reserve_datetime', date('Y-m-d H:i:s', REQUEST_TIME), '<', 'OR');
        $W = SQL::newWhere();
        $W->addWhereOpr('cache_reserve_entry_id', $eid);
        $W->addWhereOpr('cache_reserve_blog_id', $bid);
        $SQL->addWhere($W, 'OR');
        $DB->query($SQL->get(dsn()), 'exec');

        if ($start > date('Y-m-d H:i:s', REQUEST_TIME)) {
            $SQL = SQL::newInsert('cache_reserve');
            $SQL->addInsert('cache_reserve_datetime', $start);
            $SQL->addInsert('cache_reserve_entry_id', $eid);
            $SQL->addInsert('cache_reserve_blog_id', $bid);
            $SQL->addInsert('cache_reserve_type', 'start');
            $DB->query($SQL->get(dsn()), 'exec');
        }

        if ($end > date('Y-m-d H:i:s', REQUEST_TIME) && $end < '3000/12/31 23:59:59') {
            $SQL = SQL::newInsert('cache_reserve');
            $SQL->addInsert('cache_reserve_datetime', $end);
            $SQL->addInsert('cache_reserve_entry_id', $eid);
            $SQL->addInsert('cache_reserve_blog_id', $bid);
            $SQL->addInsert('cache_reserve_type', 'end');
            $DB->query($SQL->get(dsn()), 'exec');
        }

        return true;
    }

    /**
     * キャッシュ自動削除の情報を削除
     *
     * @param int $eid
     *
     * @return bool
     */
    public function deleteCacheControl($eid = EID)
    {
        if (!$eid) {
            return false;
        }

        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newDelete('cache_reserve');
        $SQL->addWhereOpr('cache_reserve_datetime', date('Y-m-d H:i:s', REQUEST_TIME), '<', 'OR');
        $SQL->addWhereOpr('cache_reserve_entry_id', $eid, '=', 'OR');
        $DB->query($SQL->get(dsn()), 'exec');

        return true;
    }

    /**
     * 指定されたリビジョンを取得
     * @param int $eid
     * @param int $rvid
     * @return array
     */
    public function getRevision($eid, $rvid)
    {
        $sql = SQL::newSelect('entry_rev');
        $sql->addWhereOpr('entry_id', $eid);
        $sql->addWhereOpr('entry_rev_id', $rvid);

        return DB::query($sql->get(dsn()), 'row');
    }

    /**
     * 現在のログインユーザーがダイレクト編集を利用可能かどうかを判定する
     *
     * @return bool
     */
    public function canUseDirectEdit(): bool
    {
        if ('on' !== config('entry_edit_inplace')) {
            return false;
        }

        if (!defined('EID')) {
            return false;
        }

        /** @var int|null $entryId */
        $entryId = EID;
        if (is_null($entryId)) {
            return false;
        }

        if (VIEW !== 'entry') { // @phpstan-ignore-line
            return false;
        }

        if (ADMIN) { // @phpstan-ignore-line
            // 管理画面はダイレクト編集は利用不可
            return false;
        }

        if (defined('RVID') && RVID !== null && RVID > 0) {
            // バージョン詳細画面はダイレクト編集は利用不可
            return false;
        }

        if (Preview::isPreviewMode()) {
            // プレビューモードはダイレクト編集は利用不可
            return false;
        }

        $entry = ACMS_RAM::entry($entryId);

        if (is_null($entry)) {
            return false;
        }

        if ($entry['entry_approval'] === 'pre_approval') {
            return false;
        }

        if (enableApproval() && !sessionWithApprovalAdministrator()) {
            // 承認機能が有効で、かつ最終承認者でない場合はダイレクト編集は利用不可
            return false;
        }

        if (
            !roleEntryUpdateAuthorization(BID, $entry) &&
            !(sessionWithContribution() && SUID == ACMS_RAM::entryUser($entry['entry_id']))
        ) {
            // ロールによる編集権限がなく、かつエントリーの所有ユーザーでない場合はダイレクト編集は利用不可
            return false;
        }

        return true;
    }

    /**
     * 現在のログインユーザーのダイレクト編集機能が有効な状態かどうかを判定する
     *
     * @return bool
     */
    public function isDirectEditEnabled(): bool
    {
        if (!$this->canUseDirectEdit()) {
            // ダイレクト編集が利用可能な状態でない場合は無効とする
            return false;
        }

        if ('on' !== config('entry_edit_inplace_enable')) {
            return false;
        }

        return true;
    }
}
