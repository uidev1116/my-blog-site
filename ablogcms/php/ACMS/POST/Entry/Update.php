<?php

class ACMS_POST_Entry_Update extends ACMS_POST_Entry
{
    /**
     * 専用のカスタムフィールドを別テーブルに保存するためのフィールド名
     *
     * @var array
     */
    protected $fieldNames = [];

    /**
     * @param Acms\Services\Entry\Lock
     */
    protected $lockService = null;

    /**
     * 専用のカスタムフィールドを別テーブルに保存する
     *
     * @param string $fieldName
     * @param int $eid
     * @param Field_Validation $Field
     * @return void
     */
    protected function saveCustomField($fieldName, $eid, $Field)
    {
    }

    /**
     * エントリーを更新
     *
     * @return Field
     */
    public function post()
    {
        if (!Entry::validateMediaUnit()) {
            httpStatusCode('500 Internal Server Error');
            return;
        }
        $this->lockService = App::make('entry.lock');
        $updatedResponse = $this->update();
        $redirect = $this->Post->get('redirect');
        $backend = $this->Post->get('backend');
        $ajax = $this->Post->get('ajaxUploadImageAccess') === 'true';

        setCookieDelFlag();
        $this->clearCache(BID, EID);

        if (is_array($updatedResponse) && !empty($redirect) && Common::isSafeUrl($redirect)) {
            $this->responseRedirect($redirect, $ajax);
        }

        if (is_array($updatedResponse)) {
            $Session = &Field::singleton('session');
            $Session->add('entry_action', 'update');
            $info = [
                'bid' => BID,
                'cid' => $updatedResponse['cid'],
                'eid' => EID,
            ];
            if ($updatedResponse['trash'] == 'trash') {
                $info['query'] = ['trash' => 'show'];
            }
            if (!empty($backend)) {
                $query = ['success' => $updatedResponse['success']];
                if ($updatedResponse['rvid']) {
                    $query['rvid'] = $updatedResponse['rvid'];
                }
                $redirect = acmsLink([
                    'bid' => BID,
                    'cid' => $updatedResponse['cid'],
                    'eid' => EID,
                    'admin' => 'entry_editor',
                    'query' => $query,
                ]);
                $this->responseRedirect($redirect, $ajax);
            }
            $this->responseRedirect(acmsLink($info), $ajax);
        }
        return $this->responseGet($ajax);
    }

    /**
     * エントリー更新
     *
     * @param mixed $exceptField
     * @return array|bool
     */
    public function update($exceptField = false)
    {
        ACMS_RAM::entry(EID, null);

        $postEntry = $this->extract('entry');
        $this->fix($postEntry);
        $customFieldCollection = [];
        $cid = $postEntry->get('category_id');
        if (empty($cid)) {
            $cid = null;
        }

        $preEntry = ACMS_RAM::entry(EID);
        $isUpdateableForMainEntry = $this->isUpdateableForMainEntry($preEntry, $postEntry); // メインエントリを更新するか判定
        $isNewVersion = $this->isNewVersion($postEntry); // 新規バージョンとして保存するか判定 $isNewVersionだったもの
        $isApproved = enableApproval() && $preEntry['entry_approval'] !== 'pre_approval';

        if (enableRevision(false) && $postEntry->get('revision_type') === 'new') {
            Entry::setNewVersion(true);
        }

        $this->validate($postEntry); // バリデート

        $field = $this->extract('field', new ACMS_Validator()); // カスタムフィールドを事前処理
        foreach ($this->fieldNames as $fieldName) {
            $customFieldCollection[$fieldName] = $this->extract($fieldName, new ACMS_Validator());
        }

        $range = $this->getRange($postEntry);

        if (!$this->Post->isValidAll()) {
            // バリデーション失敗
            $this->validateFailed($field, $range, 'update');

            AcmsLogger::info('「' . ACMS_RAM::entryTitle(EID) . '」エントリーの更新に失敗しました', [
                'isUpdateableForMainEntry' => $isUpdateableForMainEntry,
                'isNewVersion' => $isNewVersion,
                'isApproved' => $isApproved,
                'Entry' => $postEntry,
            ]);
            return false;
        }

        $units = $this->extractUnit($range); // ユニットの事前処理
        $entryData = $this->getUpdateEntryData($preEntry, $postEntry, Entry::getSummaryRange()); // エントリーの事前処理

        /**
         * エントリーの保存
         */
        if ($isUpdateableForMainEntry) {
            $primaryImageId = $this->saveUnit($units, EID, $postEntry->get('primary_image')); // ユニット（unitテーブル）を更新
            $entryData['entry_primary_image'] = $primaryImageId;
            $this->updateEntry($entryData); // エントリ（entryテーブル）を更新
            $this->saveTag(EID, $postEntry->get('tag')); // タグ（tagテーブル）を更新
            Entry::saveRelatedEntries(EID, $postEntry->getArray('related'), null, $postEntry->getArray('related_type'), $postEntry->getArray('loaded_realted_entries')); // 関連エントリ（relationship）を更新
            Entry::saveSubCategory(EID, $cid, $postEntry->get('sub_category_id')); // サブカテゴリー（entry_sub_category）を更新
            $this->saveGeometry('eid', EID, $this->extract('geometry')); // 位置情報（geo）を更新
            if (!$exceptField) {
                Common::saveField('eid', EID, $field); // フィールド（field）を更新
                foreach ($customFieldCollection as $fieldName => $customField) {
                    $this->saveCustomField($fieldName, EID, $customField);
                }
            }
            Common::saveFulltext('eid', EID, Common::loadEntryFulltext(EID)); // フルテキスト（fulltext）を更新

            if (ACMS_RAM::entryApproval(EID) === 'pre_approval') {
                AcmsLogger::info('「' . $entryData['entry_title'] . '」エントリーの作業領域を更新しました', [
                    'eid' => EID,
                    'cid' => $cid,
                ]);
            } else {
                AcmsLogger::info('「' . $entryData['entry_title'] . '」エントリーを更新しました', [
                    'eid' => EID,
                    'cid' => $cid,
                ]);
            }
        }

        /**
         * バージョンの保存
         */
        if (enableRevision(false) && get_called_class() !== 'ACMS_POST_Entry_Update_Detail') {
            $rvid = Entry::saveEntryRevision(EID, RVID, $entryData, $postEntry->get('revision_type'), $postEntry->get('revision_memo'));
            $this->saveRevisionUnit($units, $postEntry, EID, $rvid);
            Entry::saveFieldRevision(EID, $field, $rvid);
            $this->saveRevisionTag($postEntry->get('tag'), EID, $rvid);
            Entry::saveRelatedEntries(EID, $postEntry->getArray('related'), $rvid, $postEntry->getArray('related_type'), $postEntry->getArray('loaded_realted_entries'));
            Entry::saveSubCategory(EID, $cid, $postEntry->get('sub_category_id'), BID, $rvid);
            $this->saveGeometry('eid', EID, $this->extract('geometry'), $rvid);

            // エントリのカレントリビジョンを変更
            if ($isUpdateableForMainEntry) {
                $sql = SQL::newUpdate('entry');
                $sql->addUpdate('entry_current_rev_id', $rvid);
                $sql->addUpdate('entry_reserve_rev_id', 0);
                $sql->addWhereOpr('entry_id', EID);
                $sql->addWhereOpr('entry_blog_id', BID);
                DB::query($sql->get(dsn()), 'exec');
            } else {
                $revision = Entry::getRevision(EID, $rvid);
                if ($isNewVersion) {
                    AcmsLogger::info('エントリーの新規バージョンを作成しました「' . $revision['entry_title'] . '（' . $revision['entry_rev_memo'] . '）」', [
                        'eid' => EID,
                        'rvid' => $rvid,
                    ]);
                } else {
                    AcmsLogger::info('エントリーのバージョンを上書き保存しました「' . $revision['entry_title'] . '（' . $revision['entry_rev_memo'] . '）」', [
                        'eid' => EID,
                        'rvid' => $rvid,
                    ]);
                }
            }
        }
        $this->lockService->unlock(EID, $rvid); // ロック解除

        if ($isNewVersion || $isApproved) {
            $cid = ACMS_RAM::entryCategory(EID);
        }

        $SQL = SQL::newSelect('entry');
        $SQL->addSelect('entry_status');
        $SQL->addWhereOpr('entry_id', EID);
        $SQL->addWhereOpr('entry_blog_id', BID);
        $status = DB::query($SQL->get(dsn()), 'one');

        //-------------------
        // キャッシュクリア予約
        Entry::updateCacheControl($entryData['entry_start_datetime'], $entryData['entry_end_datetime'], BID, EID);

        //----------------
        // キャッシュクリア
        ACMS_POST_Cache::clearEntryPageCache(EID);

        //------
        // Hook
        if (HOOK_ENABLE) {
            $Hook = ACMS_Hook::singleton();
            $Hook->call('saveEntry', array(EID, $rvid));
            $events = array('entry:updated');
            if (
                1
                && !$isNewVersion
                && !$isApproved
                && $preEntry['entry_status'] !== 'open'
                && $status === 'open'
                && strtotime($entryData['entry_start_datetime']) <= REQUEST_TIME
                && strtotime($entryData['entry_end_datetime']) >= REQUEST_TIME
            ) {
                $events[] = 'entry:opened';
            }
            Webhook::call(BID, 'entry', $events, array(EID, $rvid));
        }

        return array(
            'eid' => EID,
            'cid' => $cid,
            'ecd' => $this->getEntryCode($postEntry),
            'ccd' => ACMS_RAM::categoryCode($cid),
            'rvid' => $rvid,
            'trash' => $status,
            'updateApproval' => $isApproved,
            'isNewVersion' => $isNewVersion,
            'success' => 1,
        );
    }

    /**
     * acms_entryテーブルを更新するか判定
     *
     * @param \Field $postEntry
     * @return boolean
     */
    protected function isUpdateableForMainEntry($preEntry, $postEntry)
    {
        if (RVID && RVID !== 1) {
            return false;
        }
        if ($this->isNewVersion($postEntry)) {
            return false;
        }
        if (sessionWithApprovalAdministrator()) {
            return true;
        }
        if (enableApproval()) {
            if ($preEntry['entry_approval'] === 'pre_approval') {
                return true;
            }
            return false;
        }
        return true;
    }

    /**
     * 新規バージョンとして保存するか判定
     *
     * @param \Field $postEntry
     * @return boolean
     */
    protected function isNewVersion($postEntry)
    {
        if (enableRevision(false) && $postEntry->get('revision_type') === 'new') {
            return true;
        }
        return false;
    }

    /**
     * バリデーション
     *
     * @param \Field $postEntry
     * @return void
     */
    protected function validate($postEntry)
    {
        if (!($cid = $postEntry->get('category_id'))) {
            $cid = null;
        }

        $postEntry->setMethod('status', 'required');
        $postEntry->setMethod('status', 'in', array('open', 'close', 'draft', 'trash'));
        $postEntry->setMethod('status', 'category', true);
        $postEntry->setMethod('title', 'required');
        if (!!($code = strval($postEntry->get('code')))) {
            if (!config('entry_code_extension')) {
                $postEntry->setMethod('code', 'reserved', !isReserved($code, false));
            }
            if (config('check_duplicate_entry_code') === 'on') {
                $postEntry->setMethod('code', 'double', !Entry::validEntryCodeDouble($code, BID, $cid, EID));
            }
        }
        $postEntry->setMethod('code', 'string', isValidCode($postEntry->get('code')));
        $postEntry->setMethod('indexing', 'required');
        $postEntry->setMethod('indexing', 'in', array('on', 'off'));
        $postEntry->setMethod('entry', 'operable', $this->isOperable());
        $postEntry->setMethod('entry', 'lock', !$this->isLocked());
        $postEntry = Entry::validTag($postEntry);

        $postEntry->validate(new ACMS_Validator());
    }

    /**
     * バリデーション失敗時の処理
     *
     * @param \Field $field
     * @param int $range
     * @return void
     */
    protected function validateFailed($field, $range, $type = 'update')
    {
        if ($field->isValid('recover_acms_Po9H2zdPW4fj', 'required')) {
            $this->addMessage('failure'); // エントリーの復元機能によるエラーの時はメッセージを出さない
        }
        $units = Entry::extractColumn($range, false); // old画像を削除しない
        $this->Post->set('step', 'reapply');
        $this->Post->set('action', $type);
        $this->Post->set('column', acmsSerialize($units));
    }

    /**
     * ユニットの事前処理 ＆ ファイル類の生成
     *
     * @param int $range
     * @return array
     */
    protected function extractUnit($range)
    {
        return Entry::extractColumn($range, true, false);
    }

    /**
     * ユニットをメインデータに保存
     *
     * @param array $units
     * @param int $eid
     * @param int $primary_image
     */
    protected function saveUnit($units, $eid, $primary_image)
    {
        $primaryImageId = 0;
        $unitIds = Entry::saveColumn($units, $eid, BID);
        $primaryImageId = empty($unitIds) ? null : (
            !$primary_image ? reset($unitIds) : (
                !empty($unitIds[$primary_image]) ? $unitIds[$primary_image] : reset($unitIds)
            )
        );
        return $primaryImageId;
    }

    /**
     * リビジョンのユニットを更新
     *
     * @param array $units
     * @param \Field $postEntry
     * @param int $eid
     * @param int $rvid
     * @return void
     */
    protected function saveRevisionUnit($units, $postEntry, $eid, $rvid)
    {
        $unitIds = Entry::saveUnitRevision($units, $eid, BID, $rvid);
        $primaryImageId = empty($unitIds) ? null : (
            !$postEntry->get('primary_image') ? reset($unitIds) : (
                !empty($unitIds[$postEntry->get('primary_image')]) ? $unitIds[$postEntry->get('primary_image')] : reset($unitIds)
            )
        );
        // primaryImageIdを更新
        $sql = SQL::newUpdate('entry_rev');
        $sql->addUpdate('entry_primary_image', $primaryImageId);
        $sql->addWhereOpr('entry_id', $eid);
        $sql->addWhereOpr('entry_rev_id', $rvid);
        $sql->addWhereOpr('entry_blog_id', BID);
        DB::query($sql->get(dsn()), 'exec');
    }

    /**
     * 続きを読むの範囲を取得
     *
     * @param mixed $postEntry
     * @return int|null
     */
    protected function getRange($postEntry)
    {
        $range = strval($postEntry->get('summary_range'));
        $range = ('' === $range) ? null : intval($range);

        return $range;
    }

    /**
     * エントリーコードを整形して取得
     *
     * @param mixed $postEntry
     * @return string
     */
    protected function getEntryCode($postEntry)
    {
        $code = trim(strval($postEntry->get('code')), '/');
        if (!empty($code) && !!config('entry_code_extension') && !strpos($code, '.')) {
            $code .= ('.' . config('entry_code_extension'));
        }
        return $code;
    }

    /**
     * 保存するエントリーデータを整形して取得
     *
     * @param mixed $preEntry
     * @param mixed $postEntry
     * @param mixed $range
     * @return array
     */
    protected function getUpdateEntryData($preEntry, $postEntry, $range)
    {
        $title = $postEntry->get('title');
        $status = $postEntry->get('status');
        $code = $this->getEntryCode($postEntry);
        $datetime = $postEntry->get('date') . ' ' . $postEntry->get('time');
        if ('open' === $status && 'draft' === ACMS_RAM::entryStatus(EID) && config('update_datetime_as_entry_open') !== 'off') {
            $datetime = date('Y-m-d H:i:s', REQUEST_TIME);
        }
        $cid = $postEntry->get('category_id');
        if (empty($cid)) {
            $cid = null;
        }
        $data = [
            'entry_category_id' => $cid,
            'entry_code' => $code,
            'entry_summary_range' => $range,
            'entry_status' => $status,
            'entry_title' => $title,
            'entry_link' => strval($postEntry->get('link')),
            'entry_datetime' => $datetime,
            'entry_start_datetime' => $this->getFixPublicDate($postEntry, $datetime),
            'entry_end_datetime' => $postEntry->get('end_date') . ' ' . $postEntry->get('end_time'),
            'entry_indexing' => $postEntry->get('indexing', 'on'),
            'entry_members_only' => $postEntry->get('members_only', 'on'),
            'entry_updated_datetime' => date('Y-m-d H:i:s', REQUEST_TIME),
        ];
        if ($preEntry['entry_approval'] !== 'pre_approval' || sessionWithApprovalAdministrator(BID, CID)) {
            $data['entry_approval'] = 'none';
        }
        return $data;
    }

    /**
     * エントリーをメインデータに保存
     * @param \Field $preEntry
     * @param \Field $postEntry
     * @param int $range
     * @param int $primaryImageId
     * @return void
     */
    protected function updateEntry($row)
    {
        $sql = SQL::newUpdate('entry');
        foreach ($row as $key => $val) {
            $sql->addUpdate($key, $val);
        }
        $sql->addWhereOpr('entry_id', EID);
        $sql->addWhereOpr('entry_blog_id', BID);
        DB::query($sql->get(dsn()), 'exec');

        $sql = SQL::newSelect('entry');
        $sql->addWhereOpr('entry_id', EID);
        $sql->addWhereOpr('entry_blog_id', BID);

        ACMS_RAM::entry(EID, DB::query($sql->get(dsn()), 'row'));
    }

    /**
     * タグをメインデータに保存
     *
     * @param int $eid
     * @param array $tags
     * @return void
     */
    protected function saveTag($eid, $tags)
    {
        $sql = SQL::newDelete('tag');
        $sql->addWhereOpr('tag_entry_id', $eid);
        DB::query($sql->get(dsn()), 'exec');
        if (!empty($tags)) {
            $tags = Common::getTagsFromString($tags);
            foreach ($tags as $sort => $tag) {
                if (isReserved($tag)) {
                    continue;
                }
                $sql = SQL::newInsert('tag');
                $sql->addInsert('tag_name', $tag);
                $sql->addInsert('tag_sort', $sort + 1);
                $sql->addInsert('tag_entry_id', $eid);
                $sql->addInsert('tag_blog_id', BID);
                DB::query($sql->get(dsn()), 'exec');
            }
        }
    }

    /**
     * リビジョンのタグを保存
     *
     * @param array $tags
     * @param int $eid
     * @param int $rvid
     * @return void
     */
    protected function saveRevisionTag($tags, $eid, $rvid)
    {
        $sql = SQL::newDelete('tag_rev');
        $sql->addWhereOpr('tag_entry_id', $eid);
        $sql->addWhereOpr('tag_rev_id', $rvid);
        DB::query($sql->get(dsn()), 'exec');

        if (!empty($tags)) {
            $tags = Common::getTagsFromString($tags);
            foreach ($tags as $sort => $tag) {
                $sql = SQL::newInsert('tag_rev');
                $sql->addInsert('tag_name', $tag);
                $sql->addInsert('tag_sort', $sort + 1);
                $sql->addInsert('tag_entry_id', $eid);
                $sql->addInsert('tag_blog_id', BID);
                $sql->addInsert('tag_rev_id', $rvid);
                DB::query($sql->get(dsn()), 'exec');
            }
        }
    }

    /**
     * エントリーの操作権限があるかチェック
     *
     * @return bool
     */
    protected function isOperable()
    {
        if (!EID) {
            return false;
        }

        if (roleAvailableUser()) {
            if (!roleAuthorization('entry_edit', BID, EID)) {
                return false;
            }
        } else {
            if (!sessionWithCompilation(BID, false)) {
                if (!sessionWithContribution(BID, false)) {
                    return false;
                }
                if (SUID <> ACMS_RAM::entryUser(EID) && (config('approval_contributor_edit_auth') === 'on' || !enableApproval(BID, CID))) {
                    return false;
                }
            }
        }
        if (enableRevision(false) && RVID > 1) {
            if (Entry::isNewVersion()) {
                return true;
            }
            $currentEntry = ACMS_RAM::entry(EID);
            if (intval($currentEntry['entry_current_rev_id']) === RVID && !sessionWithApprovalAdministrator(BID, CID)) {
                return false;
            }
            $sql = SQL::newSelect('entry_rev');
            $sql->addWhereOpr('entry_id', EID);
            $sql->addWhereOpr('entry_rev_id', RVID);
            $revision = DB::query($sql->get(dsn()), 'row');
            if ($revision) {
                if (intval($revision['entry_rev_user_id']) !== SUID && !sessionWithApprovalAdministrator(BID, CID)) {
                    return false;
                }
                if (enableApproval(BID, CID) && !sessionWithApprovalAdministrator(BID, CID)) {
                    if ($revision['entry_rev_status'] === 'approved') {
                        // 承認済みバージョンなので変更不可
                        return false;
                    }
                    if ($revision['entry_rev_status'] === 'reject') {
                        // 承認却下バージョンなので変更不可
                        return false;
                    }
                    if ($revision['entry_rev_status'] === 'trash') {
                        // 削除依頼バージョンなので変更不可
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * エントリーロックによって保存できないかチェック
     *
     * @return bool
     */
    protected function isLocked()
    {
        if (enableRevision(false) && Entry::isNewVersion()) {
            // 新規バージョンとして保存する場合は、ロックが関係ないので、OK
            return false;
        }
        if ($this->lockService->isAlertOnly()) {
            // アラートのみの設定なら、保存OK
            return false;
        }
        if ($this->lockService->getLockedUser(EID, RVID, SUID) === false) {
            // ロックがかかってない場合は、OK
            return false;
        }
        return true;
    }
}
