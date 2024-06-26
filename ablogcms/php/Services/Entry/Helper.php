<?php

namespace Acms\Services\Entry;

use Common;
use Entry;
use Storage;
use DB;
use SQL;
use ACMS_RAM;
use ACMS_POST_File;
use ACMS_POST_Image;
use ACMS_Validator;
use Field;
use AcmsLogger;
use ACMS_Hook;
use Embed\Embed;

class Helper
{
    /**
     * サマリーの表示で使うユニットの範囲を取得
     *
     * @var int
     */
    protected $summaryRange;

    /**
     * ユニット保存後のユニットデータ
     *
     * @var array
     */
    protected $savedColumn;

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
     * Entry::extractColumn 後に決定
     *
     * @return int
     */
    public function getSummaryRange()
    {
        return $this->summaryRange;
    }

    /**
     * ユニット保存後のユニットデータを取得
     * Entry::extractColumn 後に決定
     *
     * @return array
     */
    public function getSavedColumn()
    {
        return $this->savedColumn;
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
        $DB = DB::singleton(dsn());

        //------------
        // entry
        $SQL = SQL::newDelete('entry');
        $SQL->addWhereOpr('entry_id', $eid);
        $DB->query($SQL->get(dsn()), 'exec');
        ACMS_RAM::entry($eid, null);

        //----------------------
        // column, tag, comment
        if ($changeRevision) {
            $SQL = SQL::newDelete('column');
            $SQL->addWhereOpr('column_entry_id', $eid);
            $SQL->addWhereOpr('column_attr', 'acms-form', '<>');
            $DB->query($SQL->get(dsn()), 'exec');

            $SQL = SQL::newDelete('tag');
            $SQL->addWhereOpr('tag_entry_id', $eid);
            $DB->query($SQL->get(dsn()), 'exec');
        } else {
            foreach (['column', 'tag', 'comment'] as $tb) {
                $SQL = SQL::newDelete($tb);
                $SQL->addWhereOpr($tb . '_entry_id', $eid);
                $DB->query($SQL->get(dsn()), 'exec');
            }
        }

        //---------------
        // sub category
        $SQL = SQL::newDelete('entry_sub_category');
        $SQL->addWhereOpr('entry_sub_category_eid', $eid);
        $DB->query($SQL->get(dsn()), 'exec');

        //---------------
        // related entry
        $SQL    = SQL::newDelete('relationship');
        $SQL->addWhereOpr('relation_id', $eid);
        $DB->query($SQL->get(dsn()), 'exec');

        //----------
        // fulltext
        $SQL    = SQL::newDelete('fulltext');
        $SQL->addWhereOpr('fulltext_eid', $eid);
        $DB->query($SQL->get(dsn()), 'exec');

        //----------
        // archives
        if ($changeRevision === false) {
            $Field = loadEntryField($eid);
            foreach ($Field->listFields() as $fd) {
                if (
                    1
                    and !strpos($fd, '@path')
                    and !strpos($fd, '@tinyPath')
                    and !strpos($fd, '@largePath')
                    and !strpos($fd, '@squarePath')
                ) {
                    continue;
                }
                foreach ($Field->getArray($fd, true) as $path) {
                    deleteFile(ARCHIVES_DIR . $path);
                }
            }

            $SQL = SQL::newSelect('column');
            $SQL->addWhereOpr('column_entry_id', $eid);
            $q = $SQL->get(dsn());
            if ($DB->query($q, 'fetch') and ($row = $DB->fetch($q))) {
                do {
                    $type = detectUnitTypeSpecifier($row['column_type']);
                    switch ($type) {
                        case 'image':
                            if (empty($row['column_field_2'])) {
                                break;
                            }
                            $oldAry = explodeUnitData($row['column_field_2']);
                            foreach ($oldAry as $old) {
                                $path   = ARCHIVES_DIR . $old;
                                $large  = otherSizeImagePath($path, 'large');
                                $tiny   = otherSizeImagePath($path, 'tiny');
                                $square = otherSizeImagePath($path, 'square');
                                deleteFile($path);
                                deleteFile($large);
                                deleteFile($tiny);
                                deleteFile($square);
                            }
                            break;
                        case 'file':
                            if (empty($row['column_field_2'])) {
                                break;
                            }
                            $oldAry = explodeUnitData($row['column_field_2']);
                            foreach ($oldAry as $old) {
                                $path = ARCHIVES_DIR . $old;
                                deleteFile($path);
                            }
                            break;
                    }
                } while ($row = $DB->fetch($q));
            }
        }

        //-------
        // field
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
        $DB = DB::singleton(dsn());

        //----------
        // archives
        $SQL = SQL::newSelect('column_rev');
        $SQL->addWhereOpr('column_entry_id', $eid);
        $q = $SQL->get(dsn());
        if ($DB->query($q, 'fetch') and ($row = $DB->fetch($q))) {
            do {
                $type = detectUnitTypeSpecifier($row['column_type']);
                switch ($type) {
                    case 'image':
                        if (empty($row['column_field_2'])) {
                            break;
                        }
                        $oldAry = explodeUnitData($row['column_field_2']);
                        foreach ($oldAry as $old) {
                            $path   = ARCHIVES_DIR . $old;
                            $large  = otherSizeImagePath($path, 'large');
                            $tiny   = otherSizeImagePath($path, 'tiny');
                            $square = otherSizeImagePath($path, 'square');
                            deleteFile($path);
                            deleteFile($large);
                            deleteFile($tiny);
                            deleteFile($square);
                        }
                        break;
                    case 'file':
                        if (empty($row['column_field_2'])) {
                            break;
                        }
                        $oldAry = explodeUnitData($row['column_field_2']);
                        foreach ($oldAry as $old) {
                            $path   = ARCHIVES_DIR . $old;
                            deleteFile($path);
                        }
                        break;
                    case 'custom':
                        if (empty($row['column_field_6'])) {
                            break;
                        }
                        $Field = acmsUnserialize($row['column_field_6']);
                        foreach ($Field->listFields() as $fd) {
                            if (
                                1
                                && !strpos($fd, '@path')
                                && !strpos($fd, '@tinyPath')
                                && !strpos($fd, '@largePath')
                                && !strpos($fd, '@squarePath')
                            ) {
                                continue;
                            }
                            foreach ($Field->getArray($fd, true) as $i => $old) {
                                $path = ARCHIVES_DIR . $old;
                                deleteFile($path);
                            }
                        }
                        break;
                }
            } while ($row = $DB->fetch($q));
        }

        //------
        // unit
        $SQL = SQL::newDelete('column_rev');
        $SQL->addWhereOpr('column_entry_id', $eid);
        $DB->query($SQL->get(dsn()), 'exec');

        //-----
        // tag
        $SQL = SQL::newDelete('tag_rev');
        $SQL->addWhereOpr('tag_entry_id', $eid);
        $DB->query($SQL->get(dsn()), 'exec');

        //---------------
        // sub category
        $SQL = SQL::newDelete('entry_sub_category_rev');
        $SQL->addWhereOpr('entry_sub_category_eid', $eid);
        $DB->query($SQL->get(dsn()), 'exec');

        //-------
        // field
        $SQL = SQL::newSelect('entry_rev');
        $SQL->addSelect('entry_rev_id');
        $SQL->addWhereOpr('entry_id', $eid);
        if ($all = $DB->query($SQL->get(dsn()), 'all')) {
            foreach ($all as $rev) {
                $rvid = $rev['entry_rev_id'];
                $Field  = loadEntryField($eid, $rvid);
                foreach ($Field->listFields() as $fd) {
                    if (
                        1
                        and !strpos($fd, '@path')
                        and !strpos($fd, '@tinyPath')
                        and !strpos($fd, '@largePath')
                        and !strpos($fd, '@squarePath')
                    ) {
                        continue;
                    }
                    foreach ($Field->getArray($fd, true) as $i => $path) {
                        if (!Storage::isFile(ARCHIVES_DIR . $path)) {
                            continue;
                        }
                        Storage::remove(ARCHIVES_DIR . $path);
                    }
                }
                Common::saveField('eid', $eid, null, null, $rvid);
            }
        }

        //-------
        // entry
        $SQL = SQL::newDelete('entry_rev');
        $SQL->addWhereOpr('entry_id', $eid);
        $DB->query($SQL->get(dsn()), 'exec');
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
     * 削除予定の指定されたパスがDBに存在するかチェック
     *
     * @param string $type
     * @param string $path
     * @return bool
     */
    public function validateRemovePath(string $type, string $path): bool
    {
        static $oldUnitData = [];
        static $pathDefinition = [
            'image' => 'column_field_2',
            'file' => 'column_field_2',
        ];

        if (empty($oldUnitData)) {
            $unitIds = [];
            if (is_array($_POST['type'])) {
                foreach (array_keys($_POST['type']) as $i) {
                    $unitIds[] = intval($_POST['clid'][$i]);
                }
            }
            foreach (['column', 'column_rev'] as $table) {
                $sql = SQL::newSelect($table);
                $sql->addWhereIn('column_id', $unitIds);
                if ($oldUnits = DB::query($sql->get(dsn()), 'all')) {
                    $oldUnitData = array_merge($oldUnits, $oldUnitData);
                }
            }
        }
        if (!isset($pathDefinition[$type])) {
            return false;
        }
        foreach ($oldUnitData as $unit) {
            $unitType = detectUnitTypeSpecifier($unit['column_type']);
            if ($unitType === $type) {
                if ($unit[$pathDefinition[$type]] === $path) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * ユニットデータの抜き出し
     *
     * @param int $range
     * @param bool $olddel
     * @param bool $directAdd
     *
     * @return array
     */
    public function extractColumn($range = 0, $olddel = true, $directAdd = false)
    {
        $summaryRange = $range;
        if (!empty($_POST['column_object'])) {
            return unserialize(gzinflate(base64_decode($_POST['column_object']))); // @phpstan-ignore-line
        }
        $Column = [];
        $overCount = 0;
        if (!(isset($_POST['type']) and is_array($_POST['type']))) {
            return $Column;
        }

        foreach ($_POST['type'] as $i => $type) {
            $id = $_POST['id'][$i];

            // 特定指定子を含むユニットタイプ
            $actualType = $type;
            // 特定指定子を除外した、一般名のユニット種別
            $type = detectUnitTypeSpecifier($type);

            $data = [];
            $dataArray = [];
            $fileArray = [];
            $mediaArray = [];
            //------
            // text
            if ('text' === $type) {
                $data = [
                    'tag' => $_POST['text_tag_' . $id],
                ];
                if (isset($_POST['text_extend_tag_' . $id])) {
                    $data['extend_tag'] = $_POST['text_extend_tag_' . $id];
                }
                $data['text'] = implodeUnitData($_POST['text_text_' . $id]);

                if ($directAdd && strlen($data['text']) === 0) {
                    $data['text'] = config('action_direct_def_text');
                }

            //-------
            // table
            } elseif ('table' === $type) {
                $data = [
                    'table' => implodeUnitData($_POST['table_source_' . $id]),
                ];
            //-------
            // image
            } elseif ('image' === $type) {
                $caption = isset($_POST['image_caption_' . $id]) ? $_POST['image_caption_' . $id] : null;
                $Image = new ACMS_POST_Image($olddel, $directAdd);
                $imageFiles = [];

                $old = isset($_POST['image_old_' . $id]) ? $_POST['image_old_' . $id] : null;
                if ($old && !$this->validateRemovePath('image', $old)) {
                    $old = null;
                }

                //------------------
                // extra unit data
                if (is_array($caption)) {
                    $imagePathAry = [];
                    $exifAry = [];

                    foreach ($caption as $n => $val) {
                        $_old   = isset($_POST['image_old_' . $id][$n]) ? $_POST['image_old_' . $id][$n] : $old;
                        $edit   = isset($_POST['image_edit_' . $id][$n]) ? $_POST['image_edit_' . $id][$n] : $_POST['image_edit_' . $id];

                        if ($_old && !$this->validateRemovePath('image', $_old)) {
                            $_old = null;
                        }
                        if (isset($_POST['image_file_' . $id][$n]) && !empty($_POST['image_file_' . $id][$n])) {
                            $Image = new ACMS_POST_Image($olddel, true);
                            ACMS_POST_Image::base64DataToImage($_POST['image_file_' . $id][$n], 'image_file_' . $id, $n);
                        }

                        $tmp = isset($_FILES['image_file_' . $id]['tmp_name'][$n]) ? $_FILES['image_file_' . $id]['tmp_name'][$n] : '';
                        $exifData = isset($_POST['image_exif_' . $id]) ? $_POST['image_exif_' . $id] : [];

                        foreach (
                            $Image->buildAndSave(
                                $id,
                                $_old,
                                $tmp,
                                $_POST['image_size_' . $id],
                                $edit,
                                $_POST['old_image_size_' . $id]
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
                    $dataArray[]    = [
                        'path'      => implodeUnitData($imagePathAry),
                        'exif'      => implodeUnitData($exifAry),
                        'caption'   => implodeUnitData($_POST['image_caption_' . $id]),
                        'link'      => implodeUnitData($_POST['image_link_' . $id]),
                        'alt'       => implodeUnitData($_POST['image_alt_' . $id]),
                        'size'      => implodeUnitData($_POST['image_size_' . $id]),
                    ];

                //------------------
                // normal unit data
                } else {
                    if (
                        1
                        && isset($_POST['image_file_' . $id])
                        && ( 0
                            || is_array($_POST['image_file_' . $id]) && !empty($_POST['image_file_' . $id][0])
                            || !is_array($_POST['image_file_' . $id]) && !empty($_POST['image_file_' . $id])
                        )
                    ) {
                        $Image = new ACMS_POST_Image($olddel, true);
                        ACMS_POST_Image::base64DataToImage($_POST['image_file_' . $id], 'image_file_' . $id);
                    }
                    $tmp = isset($_FILES['image_file_' . $id]['tmp_name']) ? $_FILES['image_file_' . $id]['tmp_name'] : '';
                    $oldSize = isset($_POST['old_image_size_' . $id]) ? $_POST['old_image_size_' . $id] : '';
                    $exifAry = isset($_POST['image_exif_' . $id]) ? $_POST['image_exif_' . $id] : [];

                    foreach (
                        $Image->buildAndSave(
                            $id,
                            $old,
                            $tmp,
                            $_POST['image_size_' . $id],
                            $_POST['image_edit_' . $id],
                            $oldSize
                        ) as $imageData
                    ) {
                        $exif = array_shift($exifAry);
                        if (empty($imageData)) {
                            continue;
                        }
                        $imageData['exif'] = $exif;
                        $imageFiles[] = $imageData;
                    }
                    foreach ($imageFiles as $imagePath) {
                        $dataArray[]    = [
                            'path'      => $imagePath['path'],
                            'exif'      => $imagePath['exif'],
                            'caption'   => $_POST['image_caption_' . $id],
                            'link'      => $_POST['image_link_' . $id],
                            'alt'       => $_POST['image_alt_' . $id],
                            'size'      => $_POST['image_size_' . $id],
                        ];
                    }
                }

            //------
            // file
            } elseif ('file' === $type) {
                $caption = isset($_POST['file_caption_' . $id]) ? $_POST['file_caption_' . $id] : null;
                $File = new ACMS_POST_File($olddel, $directAdd);
                $files = [];
                $old = isset($_POST['file_old_' . $id]) ? $_POST['file_old_' . $id] : null;
                if ($old && !$this->validateRemovePath('file', $old)) {
                    $old = null;
                }

                //------------------
                // extra unit data
                if (is_array($caption)) {
                    $filePathAry = [];
                    foreach ($caption as $n => $val) {
                        $edit = isset($_POST['file_edit_' . $id][$n]) ? $_POST['file_edit_' . $id][$n] : (isset($_POST['file_edit_' . $id]) ? $_POST['file_edit_' . $id] : '');
                        $_old = isset($_POST['file_old_' . $id][$n]) ? $_POST['file_old_' . $id][$n] : $old;
                        if ($_old && !$this->validateRemovePath('file', $_old)) {
                            $_old = null;
                        }
                        foreach (
                            $File->buildAndSave(
                                $id,
                                $_old,
                                $_FILES['file_file_' . $id]['tmp_name'][$n],
                                $_FILES['file_file_' . $id]['name'][$n],
                                $n,
                                $edit
                            ) as $fileData
                        ) {
                            $files[$n]  = $fileData;
                        }
                        if (empty($fileData)) {
                            $files[$n] = '';
                        }
                    }
                    foreach ($files as $filePath) {
                        $filePathAry[] = $filePath;
                    }
                    $fileArray[]    = [
                        'path'      => implodeUnitData($filePathAry),
                        'caption'   => implodeUnitData($_POST['file_caption_' . $id]),
                    ];
                //------------------
                // normal unit data
                } else {
                    $edit   = isset($_POST['file_edit_' . $id]) ? $_POST['file_edit_' . $id] : '';
                    if (!isset($_FILES['file_file_' . $id])) {
                        $_FILES['file_file_' . $id]['tmp_name'] = '';
                        $_FILES['file_file_' . $id]['name'] = '';
                    }
                    foreach (
                        $File->buildAndSave(
                            $id,
                            $old,
                            $_FILES['file_file_' . $id]['tmp_name'],
                            $_FILES['file_file_' . $id]['name'],
                            0,
                            $edit
                        ) as $fileData
                    ) {
                        if (empty($fileData)) {
                            continue;
                        }
                        $files[] = $fileData;
                    }
                    foreach ($files as $filePath) {
                        $fileArray[]    = [
                            'path'      => $filePath,
                            'caption'   => $_POST['file_caption_' . $id],
                        ];
                    }
                }

            //-----
            // map
            } elseif ('map' === $type) {
                $data = [
                    'lat'   => $_POST['map_lat_' . $id],
                    'lng'   => $_POST['map_lng_' . $id],
                    'zoom'  => $_POST['map_zoom_' . $id],
                    'msg'   => $_POST['map_msg_' . $id],
                    'size'  => $_POST['map_size_' . $id],
                    'view_zoom' => isset($_POST['map_view_zoom_' . $id]) ? $_POST['map_view_zoom_' . $id] : '',
                    'view_pitch' => isset($_POST['map_view_pitch_' . $id]) ? $_POST['map_view_pitch_' . $id] : '',
                    'view_heading' => isset($_POST['map_view_heading_' . $id]) ? $_POST['map_view_heading_' . $id] : '',
                    'view_activate' => isset($_POST['map_view_activate_' . $id]) ? $_POST['map_view_activate_' . $id] : '',
                ];
            } elseif ('osmap' === $type) {
                $data = [
                    'lat'   => $_POST['map_lat_' . $id],
                    'lng'   => $_POST['map_lng_' . $id],
                    'zoom'  => $_POST['map_zoom_' . $id],
                    'msg'   => $_POST['map_msg_' . $id],
                    'size'  => $_POST['map_size_' . $id],
                ];
            //---------
            // youtube
            } elseif ('youtube' === $type) {
                $data   = [
                    'youtube_id'    => implodeUnitData($_POST['youtube_id_' . $id]),
                    'size'          => $_POST['youtube_size_' . $id],
                ];
                if ($directAdd && strlen($data['youtube_id']) === 0) {
                    $data['youtube_id'] = config('action_direct_def_youtubeid');
                }
            //---------
            // video
            } elseif ('video' === $type) {
                $data   = [
                    'video_id'  => implodeUnitData($_POST['video_id_' . $id]),
                    'size'      => $_POST['video_size_' . $id],
                ];
                if ($directAdd && strlen($data['video_id']) === 0) {
                    $data['video_id'] = config('action_direct_def_videoid');
                }
            //---------
            // eximage
            } elseif ('eximage' === $type) {
                $size   = $_POST['eximage_size_' . $id];
                $normal = $_POST['eximage_normal_' . $id];
                $large  = $_POST['eximage_large_' . $id];
                $display_size   = '';

                if (strpos($size, ':') !== false) {
                    list($size, $display_size) = preg_split('/:/', $size);
                }

                $normalPath = is_array($normal) ? $normal[0] : $normal;
                $largePath  = is_array($large) ? $large[0] : $large;
                if ('http://' != substr($normalPath, 0, 7) && 'https://' != substr($normalPath, 0, 8)) {
                    $normalPath = rtrim(DOCUMENT_ROOT, '/') . $normalPath;
                }
                if ('http://' != substr($largePath, 0, 7) && 'https://' != substr($largePath, 0, 8)) {
                    $largePath = rtrim(DOCUMENT_ROOT, '/') . $largePath;
                }

                if ($xy = Storage::getImageSize($normalPath)) {
                    if (!empty($size) and ($size < max($xy[0], $xy[1]))) {
                        if ($xy[0] > $xy[1]) {
                            $x  = $size;
                            $y  = intval(floor(($size / $xy[0]) * $xy[1]));
                        } else {
                            $y  = $size;
                            $x  = intval(floor(($size / $xy[1]) * $xy[0]));
                        }
                    } else {
                        $x  = $xy[0];
                        $y  = $xy[1];
                    }
                    $size   = $x . 'x' . $y;
                    if (!Storage::getImageSize($largePath)) {
                        $large = '';
                    }
                } else {
                    $normal = '';
                }
                if (!empty($display_size)) {
                    $size   = $size . ':' . $display_size;
                }

                $data   = [
                    'normal'    => implodeUnitData($normal),
                    'large'     => implodeUnitData($large),
                    'caption'   => implodeUnitData($_POST['eximage_caption_' . $id]),
                    'link'      => implodeUnitData($_POST['eximage_link_' . $id]),
                    'alt'       => implodeUnitData($_POST['eximage_alt_' . $id]),
                    'size'      => $size,
                ];
                if ($directAdd && strlen($data['normal']) === 0) {
                    $data['normal'] = config('action_direct_def_eximage');
                    $data['size'] = config('action_direct_def_eximage_size');
                }
            //---------
            // quote
            } elseif ('quote' === $type) {
                $data   = [
                    'quote_url' => implodeUnitData($_POST['quote_url_' . $id]),
                ];
                if ($directAdd && strlen($data['quote_url']) === 0) {
                    $data['quote_url'] = config('action_direct_def_quote_url');
                }

            //---------
            // media
            } elseif ('media' === $type) {
                $midArray = $_POST['media_id_' . $id];
                $size = $_POST['media_size_' . $id];
                $enlarged = isset($_POST['media_enlarged_' . $id]) ? $_POST['media_enlarged_' . $id] : null;
                $useIcon = isset($_POST['media_use_icon_' . $id]) ? $_POST['media_use_icon_' . $id] : null;
                $caption = isset($_POST['media_caption_' . $id]) ? $_POST['media_caption_' . $id] : null;
                $link = isset($_POST['media_link_' . $id]) ? $_POST['media_link_' . $id] : null;
                $alt = isset($_POST['media_alt_' . $id]) ? $_POST['media_alt_' . $id] : null;

                if (!is_array($midArray)) {
                    $midArray = [$midArray];
                }
                //多言語ユニットの場合
                if (is_array($caption)) {
                    $mediaArray[] = [
                        'media_id' => implodeUnitData($midArray),
                        'size' => implodeUnitData($size),
                        'enlarged' => implodeUnitData($enlarged),
                        'use_icon' => implodeUnitData($useIcon),
                        'caption' => implodeUnitData($caption),
                        'alt' => implodeUnitData($alt),
                        'link' => implodeUnitData($link)
                    ];
                // 普通のユニットの場合
                } else {
                    foreach ($midArray as $n => $mid) {
                        $mediaArray[] = [
                            'media_id' => implodeUnitData($mid),
                            'size' => implodeUnitData($size),
                            'enlarged' => implodeUnitData($enlarged),
                            'use_icon' => implodeUnitData($useIcon),
                            'caption' => implodeUnitData($caption),
                            'alt' => implodeUnitData($alt),
                            'link' => implodeUnitData($link)
                        ];
                    }
                }
            //-------
            // rich-editor
            } elseif ('rich-editor' === $type) {
                $data = [
                    'json' => implodeUnitData($_POST['rich-editor_json_' . $id])
                ];
            //-------
            // break
            } elseif ('break' === $type) {
                $data   = [
                    'label'  => implodeUnitData($_POST['break_label_' . $id]),
                ];

            //--------
            // module
            } elseif ('module' === $type) {
                $data   = [
                    'mid'   => $_POST['module_mid_' . $id],
                    'tpl'   => $_POST['module_tpl_' . $id],
                ];

            //--------
            // custom
            } elseif ('custom' === $type) {
                $Field = Common::extract('unit' . $id, new ACMS_Validator(), new Field());
                $obj = Common::getDeleteField();
                $Field->retouchCustomUnit($id);
                $data = [
                    'field' => $Field,
                ];
            } else {
                continue;
            }
            $baseCol = [
                'id'    => $id,
                'clid'  => $_POST['clid'][$i],
                'type'  => $actualType,
                'align' => $_POST['align'][$i],
                'sort'  => @intval($_POST['sort'][$i]) + $overCount,
                'attr'  => $_POST['attr'][$i],
                'group' => @$_POST['group'][$i],
                'size'  => '',
            ];

            $baseSortNum = $baseCol['sort'];
            if ('image' === $type) {
                foreach (array_reverse($dataArray) as $num => $col) {
                    if ($baseSortNum <= $summaryRange and $num > 0) {
                        $summaryRange++;
                    }
                    $baseCol['sort']    = $baseSortNum + $num;
                    if ($num > 0) {
                        $overCount++;
                        $baseCol['clid'] = '';
                        $baseCol['id']   = uniqueString();
                    }
                    $Column[] = $col + $baseCol;
                }
            } elseif ('file' === $type) {
                foreach (array_reverse($fileArray) as $num => $col) {
                    if ($baseSortNum <= $summaryRange and $num > 0) {
                        $summaryRange++;
                    }
                    $baseCol['sort'] = $baseSortNum + $num;
                    if ($num > 0) {
                        $overCount++;
                        $baseCol['clid'] = '';
                        $baseCol['id'] = uniqueString();
                    }
                    $Column[] = $col + $baseCol;
                }
            } elseif ('media' === $type) {
                foreach ($mediaArray as $num => $col) {
                    if ($baseSortNum <= $summaryRange and $num > 0) {
                        $summaryRange++;
                    }
                    $baseCol['sort'] = $baseSortNum + $num;
                    if ($num > 0) {
                        $overCount++;
                        $baseCol['clid'] = '';
                        $baseCol['id'] = uniqueString();
                    }
                    $Column[] = $col + $baseCol;
                }
            } else {
                $Column[] = $data + $baseCol;
            }
        }

        $this->summaryRange = $summaryRange;

        return $Column;
    }

    /**
     * ユニットの保存
     *
     * @param array $Column
     * @param int $eid
     * @param int $bid
     * @param bool $add
     * @param int $rvid
     *
     * @return array
     */
    function saveColumn($Column, $eid, $bid, $add = false, $rvid = null)
    {
        $DB = DB::singleton(dsn());
        $tableName = 'column';
        $asNewVersion = false;

        if (enableRevision(false) && $rvid !== null) {
            $tableName = 'column_rev';
            if (Entry::isNewVersion()) {
                $asNewVersion = true;
            }
        }
        $TMP = null;
        $offset = 0;
        if (!$add) {
            $SQL    = SQL::newDelete($tableName);
            $SQL->addWhereOpr('column_entry_id', $eid);
            $SQL->addWhereOpr('column_attr', 'acms-form', '<>');
            if ($tableName  === 'column_rev') {
                $SQL->addWhereOpr('column_rev_id', $rvid);
                $TMP = loadColumn($eid, null, $rvid);
            } else {
                $TMP = loadColumn($eid);
            }
            $DB->query($SQL->get(dsn()), 'exec');

            $arySort    = [];
            foreach ($Column as $data) {
                if (is_array($data)) {
                    $arySort[] = $data['sort'];
                }
            }
            if (!empty($arySort)) {
                $offset = min($arySort) - 1;
            }
        }
        $Res = [];

        foreach ($Column as $key => $data) {
            $id     = $data['id'];
            $type   = $data['type'];

            // 特定指定子を含むユニットタイプ
            $actualType = $type;

            // 特定指定子を除外した、一般名のユニット種別
            $type = detectUnitTypeSpecifier($type);

            $row  = [
                'column_align'      => $data['align'],
                'column_attr'       => $data['attr'],
                'column_group'      => $data['group'],
                'column_size'       => $data['size'],
                'column_type'       => $actualType,
            ];

            if ('text' === $type) {
                if (empty($data['text'])) {
                    $offset++;
                    continue;
                }
                $row['column_field_1'] = $data['text'];
                if (isset($data['extend_tag'])) {
                    $row['column_field_3'] = $data['extend_tag'];
                }
                $tokens = preg_split('@(#|\.)@', $data['tag'], -1, PREG_SPLIT_DELIM_CAPTURE);
                $row['column_field_2'] = array_shift($tokens);
                $id = '';
                $class = '';
                while ($mark = array_shift($tokens)) {
                    if (!$val = array_shift($tokens)) {
                        continue;
                    }
                    if ('#' === $mark) {
                        $id = $val;
                    } else {
                        $class = $val;
                    }
                }

                $attr = '';
                if (!empty($id)) {
                    $attr .= ' id="' . $id . '"';
                }
                if (!empty($class)) {
                    $attr .= ' class="' . $class . '"';
                }
                if (!empty($attr)) {
                    $row['column_attr'] = $attr;
                }
            } elseif ('table' === $type) {
                if (empty($data['table'])) {
                    $offset++;
                    continue;
                }
                $row['column_field_1'] = $data['table'];
            } elseif ('image' === $type) {
                if (empty($data['path'])) {
                    $offset++;
                    continue;
                }
                $row['column_field_1']  = $data['caption'];
                $row['column_field_2']  = $data['path'];
                $row['column_field_3']  = $data['link'];
                $row['column_field_4']  = $data['alt'];
                $row['column_field_6']  = $data['exif'];

                if (strpos($row['column_size'], ':') !== false) {
                    list($size, $display_size) = preg_split('/:/', $row['column_size']);
                    $row['column_size']     = $size;
                    $row['column_field_5']  = $display_size;
                }
                if ($asNewVersion) {
                    $oldAry = explodeUnitData($row['column_field_2']);
                    $newAry = [];
                    foreach ($oldAry as $old) {
                        if (in_array($old, $this->getUploadedFiles(), true)) {
                            $newAry[] = $old;
                            continue;
                        }
                        $info   = pathinfo($old);
                        $dirname = empty($info['dirname']) ? '' : $info['dirname'] . '/';
                        Storage::makeDirectory(ARCHIVES_DIR . $dirname);
                        $ext    = empty($info['extension']) ? '' : '.' . $info['extension'];
                        $newOld = $dirname . uniqueString() . $ext;

                        $path   = ARCHIVES_DIR . $old;
                        $large  = otherSizeImagePath($path, 'large');
                        $tiny   = otherSizeImagePath($path, 'tiny');
                        $square = otherSizeImagePath($path, 'square');

                        $newPath    = ARCHIVES_DIR . $newOld;
                        $newLarge   = otherSizeImagePath($newPath, 'large');
                        $newTiny    = otherSizeImagePath($newPath, 'tiny');
                        $newSquare  = otherSizeImagePath($newPath, 'square');

                        copyFile($path, $newPath);
                        copyFile($large, $newLarge);
                        copyFile($tiny, $newTiny);
                        copyFile($square, $newSquare);

                        $newAry[] = $newOld;
                    }
                    $row['column_field_2']  = implodeUnitData($newAry);
                    $Column[$key]['path']   = implodeUnitData($newAry);
                }
            } elseif ('file' === $type) {
                if (empty($data['path'])) {
                    $offset++;
                    continue;
                }
                $row['column_field_1']  = $data['caption'];
                $row['column_field_2']  = $data['path'];

                if ($asNewVersion) {
                    $oldAry = explodeUnitData($row['column_field_2']);
                    $newAry = [];
                    foreach ($oldAry as $old) {
                        if (in_array($old, $this->getUploadedFiles(), true)) {
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
                    $row['column_field_2']  = implodeUnitData($newAry);
                    $Column[$key]['path']   = implodeUnitData($newAry);
                }
            } elseif ('map' === $type) {
                if (
                    1
                    && empty($data['msg'])
                    && empty($data['lat'])
                    && empty($data['lng'])
                    && empty($data['zoom'])
                ) {
                    $offset++;
                    continue;
                }
                $row['column_field_1']  = $data['msg'];
                $row['column_field_2']  = $data['lat'];
                $row['column_field_3']  = $data['lng'];
                $row['column_field_4']  = $data['zoom'];

                if ($data['view_activate']) {
                    $row['column_field_6'] = $data['view_activate'];
                    $row['column_field_7'] = $data['view_pitch'] . ',' . $data['view_zoom'] . ',' . $data['view_heading'];
                }

                if (strpos($row['column_size'], ':') !== false) {
                    list($size, $display_size) = preg_split('/:/', $row['column_size']);
                    $row['column_size']     = $size;
                    $row['column_field_5']  = $display_size;
                }
            } elseif ('osmap' === $type) {
                if (
                    1
                    && empty($data['msg'])
                    && empty($data['lat'])
                    && empty($data['lng'])
                    && empty($data['zoom'])
                ) {
                    $offset++;
                    continue;
                }
                $row['column_field_1']  = $data['msg'];
                $row['column_field_2']  = $data['lat'];
                $row['column_field_3']  = $data['lng'];
                $row['column_field_4']  = $data['zoom'];

                if (strpos($row['column_size'], ':') !== false) {
                    list($size, $display_size) = preg_split('/:/', $row['column_size']);
                    $row['column_size']     = $size;
                    $row['column_field_5']  = $display_size;
                }
            } elseif ('youtube' === $type) {
                if (empty($data['youtube_id'])) {
                    $offset++;
                    continue;
                }
                if (preg_match(REGEX_VALID_URL, $data['youtube_id'])) {
                    $parsed_url = parse_url($data['youtube_id']);
                    if (!empty($parsed_url['query'])) {
                        $data['youtube_id'] = preg_replace('/v=([\w\-_]+).*/', '$1', $parsed_url['query']);
                    }
                }
                $row['column_field_2']  = $data['youtube_id'];

                if (strpos($row['column_size'], ':') !== false) {
                    list($size, $display_size) = preg_split('/:/', $row['column_size']);
                    $row['column_size']     = $size;
                    $row['column_field_3']  = $display_size;
                }
            } elseif ('video' === $type) {
                if (empty($data['video_id'])) {
                    $offset++;
                    continue;
                }
                if (preg_match(REGEX_VALID_URL, $data['video_id'])) {
                    $videoId = '';
                    if (HOOK_ENABLE) {
                        $Hook = ACMS_Hook::singleton();
                        $Hook->call('extendsVideoUnit', [$data['video_id'], &$videoId]);
                    }
                    if (is_string($videoId) && $videoId !== '') {
                        $data['video_id'] = $videoId;
                    } else {
                        $parsed_url = parse_url($data['video_id']);
                        if (!empty($parsed_url['query'])) {
                            $data['video_id'] = preg_replace('/v=([\w\-_]+).*/', '$1', $parsed_url['query']);
                        }
                    }
                }
                $row['column_field_2']  = $data['video_id'];

                if (strpos($row['column_size'], ':') !== false) {
                    list($size, $display_size) = preg_split('/:/', $row['column_size']);
                    $row['column_size']     = $size;
                    $row['column_field_3']  = $display_size;
                }
            } elseif ('eximage' === $type) {
                if (empty($data['normal'])) {
                    $offset++;
                    continue;
                }
                $row['column_field_1']  = $data['caption'];
                $row['column_field_2']  = $data['normal'];
                $row['column_field_3']  = $data['large'];
                $row['column_field_4']  = $data['link'];
                $row['column_field_5']  = $data['alt'];

                if (strpos($row['column_size'], ':') !== false) {
                    list($size, $display_size) = preg_split('/:/', $row['column_size']);
                    $row['column_size']     = $size;
                    $row['column_field_6']  = $display_size;
                }
            } elseif ('quote' === $type) {
                if (empty($data['quote_url'])) {
                    $offset++;
                    continue;
                }

                $row['column_field_6']  = $data['quote_url'];
                $urlAry     = explodeUnitData($row['column_field_6']);
                $oldUrlAry  = [];
                $old_       = null;

                $field1Ary  = [];
                $field2Ary  = [];
                $field3Ary  = [];
                $field4Ary  = [];
                $field5Ary  = [];
                $field7Ary  = [];

                foreach ($urlAry as $i => $url) {
                    if (preg_match(REGEX_VALID_URL, $url)) {
                        $no_change  = false;
                        $parsed_url = parse_url($url);

                        //--------------
                        // change data
                        if (empty($oldUrlAry) && !empty($data['clid']) && is_array($TMP)) {
                            foreach ($TMP as $old) {
                                if (intval($old['clid']) === intval($data['clid'])) {
                                    $old_       = $old;
                                    $oldUrlAry  = explodeUnitData($old['quote_url']);
                                    break;
                                }
                            }
                        }
                        $old_url = isset($oldUrlAry[$i]) ? $oldUrlAry[$i] : '';
                        if (strcmp($url, $old_url) === 0) {
                            $no_change      = true;
                            $site_nameAry   = explodeUnitData($old_['site_name']);
                            $field1Ary[]    = isset($site_nameAry[$i]) ? $site_nameAry[$i] : '';

                            $authorAry      = explodeUnitData($old_['author']);
                            $field2Ary[]    = isset($authorAry[$i]) ? $authorAry[$i] : '';

                            $titleAry       = explodeUnitData($old_['title']);
                            $field3Ary[]    = isset($titleAry[$i]) ? $titleAry[$i] : '';

                            $descriptionAry = explodeUnitData($old_['description']);
                            $field4Ary[]    = isset($descriptionAry[$i]) ? $descriptionAry[$i] : '';

                            $imageAry       = explodeUnitData($old_['image']);
                            $field5Ary[]    = isset($imageAry[$i]) ? $imageAry[$i] : '';

                            $htmlAry        = explodeUnitData($old_['html']);
                            $field7Ary[]    = isset($htmlAry[$i]) ? $htmlAry[$i] : '';
                        }
                        if (!$no_change) {
                            $html = '';
                            if (HOOK_ENABLE) {
                                $Hook = ACMS_Hook::singleton();
                                $Hook->call('extendsQuoteUnit', [$url, &$html]);
                            }

                            if (is_string($html) && $html !== '') {
                                $field7Ary[] = $html;
                            } else {
                                $existsGraphInfo = false;
                                try {
                                    $graph = Embed::create($url);
                                    if ($graph) {
                                        $existsGraphInfo = true;
                                        $field1Ary[] = $graph->providerName;
                                        $field2Ary[] = $graph->authorName;
                                        $field3Ary[] = $graph->title;
                                        $field4Ary[] = $graph->description;
                                        $field5Ary[] = $graph->image;
                                    }
                                } catch (\Exception $e) {
                                }
                                if (!$existsGraphInfo) {
                                    $field1Ary[] = '';
                                    $field2Ary[] = '';
                                    $field3Ary[] = '';
                                    $field4Ary[] = '';
                                    $field5Ary[] = '';
                                }
                            }
                        }
                    }
                }
                $row['column_field_1'] = implodeUnitData($field1Ary);
                $row['column_field_2'] = implodeUnitData($field2Ary);
                $row['column_field_3'] = implodeUnitData($field3Ary);
                $row['column_field_4'] = implodeUnitData($field4Ary);
                $row['column_field_5'] = implodeUnitData($field5Ary);
                $row['column_field_7'] = implodeUnitData($field7Ary);
            } elseif ('media' === $type) {
                if (empty($data['media_id'])) {
                    $offset++;
                    continue;
                }

                $row['column_field_1'] = $data['media_id'];
                $row['column_field_2'] = $data['caption'];
                $row['column_field_3'] = $data['alt'];
                $row['column_field_4'] = $data['enlarged'];
                $row['column_field_5'] = $data['use_icon'];
                $row['column_field_7'] = $data['link'];

                if (strpos($row['column_size'], ':') !== false) {
                    list($size, $display_size) = preg_split('/:/', $row['column_size']);
                    $row['column_size']     = $size;
                    $row['column_field_6']  = $display_size;
                }
            } elseif ('rich-editor' === $type) {
                if (empty($data['json'])) {
                    $offset++;
                    continue;
                }
                $row['column_field_1'] = $data['json'];
            } elseif ('break' === $type) {
                if (empty($data['label'])) {
                    $offset++;
                    continue;
                }
                $row['column_field_1']  = $data['label'];
            } elseif ('module' === $type) {
                if (empty($data['mid'])) {
                    $offset++;
                    continue;
                }
                $row['column_field_1']  = $data['mid'];
                $row['column_field_2']  = $data['tpl'];
            } elseif ('custom' === $type) {
                if (empty($data['field'])) {
                    $offset++;
                    continue;
                }
                $row['column_field_6'] = acmsSerialize($data['field']);

                if ($asNewVersion) {
                    $Field = $data['field'];
                    foreach ($Field->listFields() as $fd) {
                        if (
                            1
                            && !strpos($fd, '@path')
                            && !strpos($fd, '@tinyPath')
                            && !strpos($fd, '@largePath')
                            && !strpos($fd, '@squarePath')
                        ) {
                            continue;
                        }
                        $set = false;
                        foreach ($Field->getArray($fd, true) as $i => $old) {
                            if (in_array($old, $this->getUploadedFiles(), true)) {
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
                                $Field->delete($fd);
                                $set = true;
                            }
                            $Field->add($fd, $newOld);
                        }
                    }
                    $row['column_field_6'] = acmsSerialize($Field);
                    $Column[$key]['field'] = $Field;
                }
            } else {
                $offset++;
                continue;
            }

            if (!empty($data['clid'])) {
                $clid   = intval($data['clid']);
                $SQL    = SQL::newDelete($tableName);
                $SQL->addWhereOpr('column_id', $clid);
                if ($tableName  === 'column_rev') {
                    $SQL->addWhereOpr('column_rev_id', $rvid);
                }
                $DB->query($SQL->get(dsn()), 'exec');
            } else {
                $clid   = $DB->query(SQL::nextval('column_id', dsn()), 'seq');
            }

            $sort   = intval($data['sort'] - $offset);

            $SQL    = SQL::newSelect($tableName);
            $SQL->setSelect('column_id');
            $SQL->addWhereOpr('column_sort', $sort);
            $SQL->addWhereOpr('column_entry_id', intval($eid));
            $SQL->addWhereOpr('column_blog_id', intval($bid));
            if ($tableName  === 'column_rev') {
                $SQL->addWhereOpr('column_rev_id', $rvid);
            }
            if ($DB->query($SQL->get(dsn()), 'one')) {
                $SQL    = SQL::newUpdate($tableName);
                $SQL->setUpdate('column_sort', SQL::newOpr('column_sort', 1, '+'));
                $SQL->addWhereOpr('column_sort', $sort, '>=');
                $SQL->addWhereOpr('column_entry_id', intval($eid));
                $SQL->addWhereOpr('column_blog_id', intval($bid));
                if ($tableName  === 'column_rev') {
                    $SQL->addWhereOpr('column_rev_id', $rvid);
                }
                $DB->query($SQL->get(dsn()), 'exec');
            }

            $SQL    = SQL::newInsert($tableName);
            foreach ($row as $fd => $val) {
                $SQL->addInsert($fd, strval($val));
            }
            $SQL->addInsert('column_id', intval($clid));
            $SQL->addInsert('column_sort', intval($sort));
            $SQL->addInsert('column_entry_id', intval($eid));
            $SQL->addInsert('column_blog_id', intval($bid));
            if ($tableName  === 'column_rev') {
                $SQL->addInsert('column_rev_id', $rvid);
            }
            $DB->query($SQL->get(dsn()), 'exec');

            if ('image' === $type || 'media' === $type) {
                $Res[$id] = $clid;
            }
        }

        $this->savedColumn = $Column;

        return $Res;
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
        if (!enableRevision(false)) {
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
     * ユニットのバージョンを保存
     *
     * @param array $units
     * @param int $eid
     * @param int $bid
     * @param int $rvid
     *
     * @return array|bool
     */
    public function saveUnitRevision($units, $eid, $bid, $rvid)
    {
        if (!enableRevision(false)) {
            return false;
        }
        $unitIds = $this->saveColumn($units, $eid, $bid, false, $rvid);

        return $unitIds;
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
        if (!enableRevision(false)) {
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
}
