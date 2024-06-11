<?php

class ACMS_POST_Entry_Duplicate extends ACMS_POST_Entry
{
    public function post()
    {
        $eid = idval($this->Post->get('eid', EID));
        if (!$this->validate($eid)) {
            AcmsLogger::info('「' . ACMS_RAM::entryTitle($eid) . '」エントリーを複製に失敗しました');
        }
        $newEid = $this->duplicate($eid);
        $cid = idval($this->Post->get('cid'));

        AcmsLogger::info('「' . ACMS_RAM::entryTitle($eid) . '」エントリーを複製しました', [
            'newEID' => $newEid,
        ]);

        $this->redirect(acmsLink([
            'bid'   => BID,
            'cid'   => $cid,
            'eid'   => $newEid,
        ]));
    }

    /**
     * エントリーを複製する
     * @param int $eid 複製元のエントリーID
     * @return int 複製先のエントリーID
     */
    protected function duplicate($eid)
    {
        $DB = DB::singleton(dsn());
        $newEid = $DB->query(SQL::nextval('entry_id', dsn()), 'seq');
        if (enableApproval(BID, CID) && !sessionWithApprovalAdministrator(BID, CID)) {
            $this->approvalDupe($eid, $newEid);
        } else {
            $this->dupe($eid, $newEid);
        }
        return $newEid;
    }

    /**
     * エントリーの複製を許可するかどうかを検証する
     * @param int $eid エントリーID
     * @return bool
     */
    protected function validate($eid)
    {
        if (empty($eid)) {
            return false;
        }
        $bid = ACMS_RAM::entryBlog($eid);
        if (roleAvailableUser()) {
            if (!roleAuthorization('entry_edit', $bid, $eid)) {
                return false;
            }
        } else {
            if (!sessionWithCompilation($bid, false)) {
                if (!sessionWithContribution($bid, false)) {
                    return false;
                }
                if (SUID <> ACMS_RAM::entryUser($eid) && !enableApproval($bid, CID)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * カスタムフィールドを複製する
     * @param \Field &$Field
     * @return void
     */
    protected function fieldDupe(&$Field)
    {
        foreach ($Field->listFields() as $fd) {
            if (preg_match('/(.*?)@path$/', $fd, $match)) {
                $fieldBase = $match[1];
                $set = false;
                foreach ($Field->getArray($fieldBase . '@path') as $i => $path) {
                    $fullpath = ARCHIVES_DIR . $path;
                    if (!Storage::isFile($fullpath)) {
                        if ($i === 0) {
                            $Field->deleteField($fieldBase . '@path');
                            $Field->deleteField($fieldBase . '@largePath');
                            $Field->deleteField($fieldBase . '@tinyPath');
                            $Field->deleteField($fieldBase . '@squarePath');
                        }

                        $Field->addField($fieldBase . '@path', '');
                        $Field->addField($fieldBase . '@largePath', '');
                        $Field->addField($fieldBase . '@tinyPath', '');
                        $Field->addField($fieldBase . '@squarePath', '');

                        continue;
                    }

                    if (!$set) {
                        $Field->delete($fieldBase . '@path');
                        $Field->delete($fieldBase . '@largePath');
                        $Field->delete($fieldBase . '@tinyPath');
                        $Field->delete($fieldBase . '@squarePath');
                        $set = true;
                    }

                    $info = pathinfo($path);
                    $dirname = empty($info['dirname']) ? '' : $info['dirname'] . '/';
                    Storage::makeDirectory(ARCHIVES_DIR . $dirname);

                    $largeFullpath = otherSizeImagePath($fullpath, 'large');
                    $tinyFullpath = otherSizeImagePath($fullpath, 'tiny');
                    $squareFullpath = otherSizeImagePath($fullpath, 'square');

                    $newFullpath = $this->createUniqueFilepath($fullpath);
                    $newLargeFullpath = otherSizeImagePath($newFullpath, 'large');
                    $newTinyFullpath = otherSizeImagePath($newFullpath, 'tiny');
                    $newSquareFullpath = otherSizeImagePath($newFullpath, 'square');

                    if (Storage::isReadable($fullpath)) {
                        copyFile($fullpath, $newFullpath);
                        $newPath = substr($newFullpath, strlen(ARCHIVES_DIR));
                        $Field->add($fieldBase . '@path', $newPath);
                    }

                    if (Storage::isReadable($largeFullpath)) {
                        copyFile($largeFullpath, $newLargeFullpath);
                        $newLargePath = substr($newLargeFullpath, strlen(ARCHIVES_DIR));
                        $Field->add($fieldBase . '@largePath', $newLargePath);
                    }

                    if (Storage::isReadable($tinyFullpath)) {
                        copyFile($tinyFullpath, $newTinyFullpath);
                        $newTinyPath = substr($newTinyFullpath, strlen(ARCHIVES_DIR));
                        $Field->add($fieldBase . '@tinyPath', $newTinyPath);
                    }

                    if (Storage::isReadable($squareFullpath)) {
                        copyFile($squareFullpath, $newSquareFullpath);
                        $newSquarePath = substr($newSquareFullpath, strlen(ARCHIVES_DIR));
                        $Field->add($fieldBase . '@squarePath', $newSquarePath);
                    }
                }
            }
        }
    }

    /**
     * 関連エントリーの複製
     * @param int $eid 複製元のエントリーID
     * @param int $newEid 複製先のエントリーID
     * @return void
     */
    protected function relationDupe($eid, $newEid)
    {
        $DB     = DB::singleton(dsn());

        $SQL    = SQL::newSelect('relationship');
        $SQL->addWhereOpr('relation_id', $eid);
        $all    = $DB->query($SQL->get(dsn()), 'all');

        foreach ($all as $row) {
            $SQL = SQL::newInsert('relationship');
            $SQL->addInsert('relation_id', $newEid);
            $SQL->addInsert('relation_eid', $row['relation_eid']);
            $SQL->addInsert('relation_type', $row['relation_type']);
            $SQL->addInsert('relation_order', $row['relation_order']);
            $DB->query($SQL->get(dsn()), 'exec');
        }
    }

    /**
     * 位置情報の複製
     * @param int $eid 複製元のエントリーID
     * @param int $newEid 複製先のエントリーID
     * @return void
     */
    protected function geoDuplicate($eid, $newEid)
    {
        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('geo');
        $SQL->addWhereOpr('geo_eid', $eid);
        if ($row = $DB->query($SQL->get(dsn()), 'row')) {
            $SQL = SQL::newInsert('geo');
            $SQL->addInsert('geo_eid', $newEid);
            $SQL->addInsert('geo_geometry', $row['geo_geometry']);
            $SQL->addInsert('geo_zoom', $row['geo_zoom']);
            $SQL->addInsert('geo_blog_id', $row['geo_blog_id']);
            $DB->query($SQL->get(dsn()), 'exec');
        }
    }

    /**
     * 承認機能が有効な場合のエントリーの複製
     * @param int $eid 複製元のエントリーID
     * @param int $newEid 複製先のエントリーID
     * @return void
     */
    protected function approvalDupe($eid, $newEid)
    {
        $DB         = DB::singleton(dsn());
        $bid        = ACMS_RAM::entryBlog($eid);
        $approval   = ACMS_RAM::entryApproval($eid);
        $sourceRev  = false;

        if ($approval === 'pre_approval') {
            $sourceRev  = true;
        }

        //--------
        // column
        $map    = [];
        if ($sourceRev) {
            $SQL    = SQL::newSelect('column_rev');
            $SQL->addWhereOpr('column_rev_id', 1);
        } else {
            $SQL    = SQL::newSelect('column');
        }
        $SQL->addWhereOpr('column_entry_id', $eid);
        $SQL->addWhereOpr('column_blog_id', $bid);
        $q  = $SQL->get(dsn());
        if ($DB->query($q, 'fetch') and ($row = $DB->fetch($q))) {
            do {
                $type = detectUnitTypeSpecifier($row['column_type']);
                switch ($type) {
                    case 'image':
                        $row['column_field_2']  = $this->duplicateImageUnit($row['column_field_2']);
                        break;
                    case 'file':
                        $row['column_field_2'] = $this->duplicateFileUnit($row['column_field_2']);
                        break;
                    case 'custom':
                        $row['column_field_6'] = $this->duplicateCustomUnit($row['column_field_6']);
                        break;
                    default:
                        break;
                }
                $newClid = $DB->query(SQL::nextval('column_id', dsn()), 'seq');
                $map[intval($row['column_id'])] = $newClid;
                $row['column_id']       = $newClid;
                $row['column_entry_id'] = $newEid;

                $SQL    = SQL::newInsert('column_rev');
                foreach ($row as $fd => $val) {
                    $SQL->addInsert($fd, $val);
                }
                if (!$sourceRev) {
                    $SQL->addInsert('column_rev_id', 1);
                }
                $DB->query($SQL->get(dsn()), 'exec');
            } while ($row = $DB->fetch($q));
        }

        //-------
        // entry
        if ($sourceRev) {
            $SQL    = SQL::newSelect('entry_rev');
            $SQL->addWhereOpr('entry_rev_id', 1);
        } else {
            $SQL    = SQL::newSelect('entry');
        }
        $SQL->addWhereOpr('entry_id', $eid);
        $SQL->addWhereOpr('entry_blog_id', $bid);
        $row = $DB->query($SQL->get(dsn()), 'row');
        $title  = $row['entry_title'] . config('entry_title_duplicate_suffix');
        $code   = ('on' == config('entry_code_title')) ? stripWhitespace($title) : config('entry_code_prefix') . $newEid;
        if (!!config('entry_code_extension') and !strpos($code, '.')) {
            $code .= ('.' . config('entry_code_extension'));
        }

        $uid    = intval($row['entry_user_id']);
        if (!($cid = intval($row['entry_category_id']))) {
            $cid = null;
        };

        //------
        // sort
        $SQL    = SQL::newSelect('entry');
        $SQL->setSelect('entry_sort');
        $SQL->addWhereOpr('entry_blog_id', $bid);
        $SQL->setOrder('entry_sort', 'DESC');
        $SQL->setLimit(1);
        $esort  = intval($DB->query($SQL->get(dsn()), 'one')) + 1;

        $SQL    = SQL::newSelect('entry');
        $SQL->setSelect('entry_user_sort');
        $SQL->addWhereOpr('entry_user_id', $uid);
        $SQL->addWhereOpr('entry_blog_id', $bid);
        $SQL->setOrder('entry_user_sort', 'DESC');
        $SQL->setLimit(1);
        $usort  = intval($DB->query($SQL->get(dsn()), 'one')) + 1;

        $SQL    = SQL::newSelect('entry');
        $SQL->setSelect('entry_category_sort');
        $SQL->addWhereOpr('entry_category_id', $cid);
        $SQL->addWhereOpr('entry_blog_id', $bid);
        $SQL->setOrder('entry_category_sort', 'DESC');
        $SQL->setLimit(1);
        $csort  = intval($DB->query($SQL->get(dsn()), 'one')) + 1;

        $row['entry_id']        = $newEid;
        $row['entry_status']    = 'close';
        $row['entry_title']     = $title;
        $row['entry_code']      = $code;
        if (config('update_datetime_as_duplicate_entry') !== 'off') {
            $row['entry_datetime'] = date('Y-m-d H:i:s', REQUEST_TIME);
        }
        $row['entry_posted_datetime']   = date('Y-m-d H:i:s', REQUEST_TIME);
        $row['entry_updated_datetime']  = date('Y-m-d H:i:s', REQUEST_TIME);
        $row['entry_hash']              = md5(SYSTEM_GENERATED_DATETIME . date('Y-m-d H:i:s', REQUEST_TIME));
        $row['entry_primary_image']     = !empty($map[$row['entry_primary_image']]) ? $map[$row['entry_primary_image']] : null;
        $row['entry_sort']              = $esort;
        $row['entry_user_sort']         = $usort;
        $row['entry_category_sort']     = $csort;
        $row['entry_user_id']           = SUID;
        $SQL    = SQL::newInsert('entry');
        foreach ($row as $fd => $val) {
            if (
                !in_array($fd, [
                    'entry_approval',
                    'entry_approval_public_point',
                    'entry_approval_reject_point',
                    'entry_last_update_user_id',
                    'entry_rev_id',
                    'entry_rev_status',
                    'entry_rev_memo',
                    'entry_rev_user_id',
                    'entry_rev_datetime',
                    'entry_current_rev_id',
                    'entry_reserve_rev_id'
                ], true)
            ) {
                $SQL->addInsert($fd, $val);
            }
        }
        $SQL->addInsert('entry_approval', 'pre_approval');
        $SQL->addInsert('entry_last_update_user_id', SUID);
        $DB->query($SQL->get(dsn()), 'exec');

        $SQL    = SQL::newInsert('entry_rev');
        foreach ($row as $fd => $val) {
            if (
                !in_array($fd, [
                    'entry_current_rev_id',
                    'entry_reserve_rev_id',
                    'entry_last_update_user_id',
                    'entry_rev_id',
                    'entry_rev_user_id',
                    'entry_rev_datetime'
                ], true)
            ) {
                $SQL->addInsert($fd, $val);
            }
        }
        $SQL->addInsert('entry_rev_id', 1);
        $SQL->addInsert('entry_rev_user_id', SUID);
        $SQL->addInsert('entry_rev_datetime', date('Y-m-d H:i:s', REQUEST_TIME));
        $DB->query($SQL->get(dsn()), 'exec');

        //-----
        // tag
        $SQL    = SQL::newSelect('tag');
        $SQL->addWhereOpr('tag_entry_id', $eid);
        $SQL->addWhereOpr('tag_blog_id', $bid);
        $q  = $SQL->get(dsn());
        if ($DB->query($q, 'fetch') and ($row = $DB->fetch($q))) {
            do {
                $row['tag_entry_id']    = $newEid;
                $Insert = SQL::newInsert('tag_rev');
                foreach ($row as $fd => $val) {
                    $Insert->addInsert($fd, $val);
                }
                if (!$sourceRev) {
                    $Insert->addInsert('tag_rev_id', 1);
                }
                $DB->query($Insert->get(dsn()), 'exec');
            } while ($row = $DB->fetch($q));
        }

        //--------------
        // sub category
        if ($sourceRev) {
            $subCategory = loadSubCategories($eid, 1);
        } else {
            $subCategory = loadSubCategories($eid);
        }
        Entry::saveSubCategory($newEid, $cid, implode(',', $subCategory['id']), $bid, 1);

        //-------
        // field
        if ($sourceRev) {
            $Field  = loadEntryField($eid, 1);
        } else {
            $Field  = loadEntryField($eid);
        }
        $this->fieldDupe($Field);
        Entry::saveFieldRevision($newEid, $Field, 1);
    }

    /**
     * エントリーの複製
     * @param int $eid 複製元のエントリーID
     * @param int $newEid 複製先のエントリーID
     * @return void
     */
    protected function dupe($eid, $newEid)
    {
        $DB     = DB::singleton(dsn());
        $bid    = ACMS_RAM::entryBlog($eid);

        //--------
        // column
        $map    = [];
        $SQL    = SQL::newSelect('column');
        $SQL->addWhereOpr('column_entry_id', $eid);
        $SQL->addWhereOpr('column_blog_id', $bid);
        $q  = $SQL->get(dsn());
        if ($DB->query($q, 'fetch') and ($row = $DB->fetch($q))) {
            do {
                $type = detectUnitTypeSpecifier($row['column_type']);
                switch ($type) {
                    case 'image':
                        $row['column_field_2'] = $this->duplicateImageUnit($row['column_field_2']);
                        break;
                    case 'file':
                        $row['column_field_2'] = $this->duplicateFileUnit($row['column_field_2']);
                        break;
                    case 'custom':
                        $row['column_field_6'] = $this->duplicateCustomUnit($row['column_field_6']);
                        break;
                    default:
                        break;
                }
                $newClid    = $DB->query(SQL::nextval('column_id', dsn()), 'seq');
                $map[intval($row['column_id'])] = $newClid;
                $row['column_id']       = $newClid;
                $row['column_entry_id'] = $newEid;

                $SQL    = SQL::newInsert('column');
                foreach ($row as $fd => $val) {
                    $SQL->addInsert($fd, $val);
                }
                $DB->query($SQL->get(dsn()), 'exec');
            } while ($row = $DB->fetch($q));
        }

        //-------
        // entry
        $SQL    = SQL::newSelect('entry');
        $SQL->addWhereOpr('entry_id', $eid);
        $SQL->addWhereOpr('entry_blog_id', $bid);
        $row = $DB->query($SQL->get(dsn()), 'row');
        $title  = $row['entry_title'] . config('entry_title_duplicate_suffix');
        $code   = ('on' == config('entry_code_title')) ? stripWhitespace($title) : config('entry_code_prefix') . $newEid;
        if (!!config('entry_code_extension') and !strpos($code, '.')) {
            $code .= ('.' . config('entry_code_extension'));
        }

        $uid    = intval($row['entry_user_id']);
        if (!($cid = intval($row['entry_category_id']))) {
            $cid = null;
        };

        //------
        // sort
        $SQL    = SQL::newSelect('entry');
        $SQL->setSelect('entry_sort');
        $SQL->addWhereOpr('entry_blog_id', $bid);
        $SQL->setOrder('entry_sort', 'DESC');
        $SQL->setLimit(1);
        $esort  = intval($DB->query($SQL->get(dsn()), 'one')) + 1;

        $SQL    = SQL::newSelect('entry');
        $SQL->setSelect('entry_user_sort');
        $SQL->addWhereOpr('entry_user_id', $uid);
        $SQL->addWhereOpr('entry_blog_id', $bid);
        $SQL->setOrder('entry_user_sort', 'DESC');
        $SQL->setLimit(1);
        $usort  = intval($DB->query($SQL->get(dsn()), 'one')) + 1;

        $SQL    = SQL::newSelect('entry');
        $SQL->setSelect('entry_category_sort');
        $SQL->addWhereOpr('entry_category_id', $cid);
        $SQL->addWhereOpr('entry_blog_id', $bid);
        $SQL->setOrder('entry_category_sort', 'DESC');
        $SQL->setLimit(1);
        $csort  = intval($DB->query($SQL->get(dsn()), 'one')) + 1;

        $row['entry_id']        = $newEid;
        $row['entry_status']    = 'close';
        $row['entry_title']     = $title;
        $row['entry_code']      = $code;
        if (config('update_datetime_as_duplicate_entry') !== 'off') {
            $row['entry_datetime'] = date('Y-m-d H:i:s', REQUEST_TIME);
        }
        $row['entry_posted_datetime']   = date('Y-m-d H:i:s', REQUEST_TIME);
        $row['entry_updated_datetime']  = date('Y-m-d H:i:s', REQUEST_TIME);
        $row['entry_hash']              = md5(SYSTEM_GENERATED_DATETIME . date('Y-m-d H:i:s', REQUEST_TIME));
        $row['entry_primary_image']     = !empty($map[$row['entry_primary_image']]) ? $map[$row['entry_primary_image']] : null;
        $row['entry_sort']              = $esort;
        $row['entry_user_sort']         = $usort;
        $row['entry_category_sort']     = $csort;
        $row['entry_user_id']           = SUID;
        $SQL    = SQL::newInsert('entry');
        foreach ($row as $fd => $val) {
            if ($fd === 'entry_current_rev_id' || $fd === 'entry_reserve_rev_id') {
                continue;
            }
            $SQL->addInsert($fd, $val);
        }
        $DB->query($SQL->get(dsn()), 'exec');

        //-----
        // tag
        $SQL    = SQL::newSelect('tag');
        $SQL->addWhereOpr('tag_entry_id', $eid);
        $SQL->addWhereOpr('tag_blog_id', $bid);
        $q  = $SQL->get(dsn());
        if ($DB->query($q, 'fetch') and ($row = $DB->fetch($q))) {
            do {
                $row['tag_entry_id']    = $newEid;
                $Insert = SQL::newInsert('tag');
                foreach ($row as $fd => $val) {
                    $Insert->addInsert($fd, $val);
                }
                $DB->query($Insert->get(dsn()), 'exec');
            } while ($row = $DB->fetch($q));
        }

        //--------------
        // sub category
        $subCategory = loadSubCategories($eid);
        Entry::saveSubCategory($newEid, $cid, implode(',', $subCategory['id']));

        //-------
        // field
        $Field  = loadEntryField($eid);
        $this->fieldDupe($Field);
        Common::saveField('eid', $newEid, $Field);
        Common::saveFulltext('eid', $newEid, Common::loadEntryFulltext($newEid));

        //---------------
        // related entry
        $this->relationDupe($eid, $newEid);

        //----------
        // geo data
        $this->geoDuplicate($eid, $newEid);
    }

    /**
     * 画像ユニットの複製
     * @param string $data ユニットデータ
     * @return string 複製後のユニットデータ
     */
    protected function duplicateImageUnit(string $data): string
    {
        $imagePaths = explodeUnitData($data);
        $newImagePaths = [];
        foreach ($imagePaths as $imagePath) {
            $fullpath = ARCHIVES_DIR . $imagePath;
            $newFullpath = $this->createUniqueFilepath($fullpath);

            $largeFullpath = otherSizeImagePath($fullpath, 'large');
            $tinyFullpath = otherSizeImagePath($fullpath, 'tiny');
            $squareFullpath = otherSizeImagePath($fullpath, 'square');

            $newLargeFullpath = otherSizeImagePath($newFullpath, 'large');
            $newTinyFullpath = otherSizeImagePath($newFullpath, 'tiny');
            $newSquareFullpath = otherSizeImagePath($newFullpath, 'square');
            if (Storage::isReadable($fullpath)) {
                copyFile($fullpath, $newFullpath);
            }
            if (Storage::isReadable($largeFullpath)) {
                copyFile($largeFullpath, $newLargeFullpath);
            }
            if (Storage::isReadable($tinyFullpath)) {
                copyFile($tinyFullpath, $newTinyFullpath);
            }
            if (Storage::isReadable($squareFullpath)) {
                copyFile($squareFullpath, $newSquareFullpath);
            }
            $newImagePaths[] = substr($newFullpath, strlen(ARCHIVES_DIR));
        }
        return implodeUnitData($newImagePaths);
    }

    /**
     * ファイルユニットの複製
     * @param string $data ユニットデータ
     * @return string 複製後のユニットデータ
     */
    protected function duplicateFileUnit(string $data): string
    {
        $filePaths = explodeUnitData($data);
        $newFilePaths = [];
        foreach ($filePaths as $filePath) {
            $fullpath = ARCHIVES_DIR . $filePath;
            $newFullpath = $this->createUniqueFilepath($fullpath);
            if (Storage::isReadable($fullpath)) {
                copyFile($fullpath, $newFullpath);
            }

            $newFilePaths[] = substr($newFullpath, strlen(ARCHIVES_DIR));
        }
        return implodeUnitData($newFilePaths);
    }

    /**
     * カスタムユニットの複製
     * @param string $data ユニットデータ
     * @return string 複製後のユニットデータ
     */
    protected function duplicateCustomUnit(string $data): string
    {
        $field = acmsUnserialize($data);
        if (!($field instanceof Field)) {
            return $data;
        }
        $this->fieldDupe($field);
        return acmsSerialize($field);
    }

    /**
     * 複製時に衝突しないファイル名を生成する
     * @param string $path ファイルパス
     * @return string 衝突しないファイルパス
     */
    private function createUniqueFilepath(string $path): string
    {
        if (config('entry_duplicate_random_filename') !== 'off') {
            $fileinfo = pathinfo($path);
            return $fileinfo['dirname'] . '/' . uniqueString() . '.' . $fileinfo['extension'];
        }
        return Storage::uniqueFilePath($path);
    }
}
