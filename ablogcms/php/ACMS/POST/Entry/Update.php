<?php

class ACMS_POST_Entry_Update extends ACMS_POST_Entry
{
    /**
     * @var array
     */
    var $fieldNames  = array ();

    /**
     * @see ACMS_User_POST_EntryExtendSample_Update
     *
     * @param string            $fieldName
     * @param int               $eid
     * @param Field_Validation  $Field
     * @return void
     */
    function saveCustomField($fieldName, $eid, $Field)
    {

    }

    function copyFieldArchives($from, $to, $preApproval=false)
    {
        //----------------------------------
        // カスタムフィールドファイルを一時コピー
        $_RVID = RVID;
        if ( $preApproval ) {
            $_RVID = 1;
        }
        $revisionField  = loadEntryField(EID, $_RVID);
        foreach ( $revisionField->listFields() as $fd ) {
            if ( 1
                and !strpos($fd, '@path')
                and !strpos($fd, '@tinyPath')
                and !strpos($fd, '@largePath')
                and !strpos($fd, '@squarePath')
            ) {
                continue;
            }
            foreach ( $revisionField->getArray($fd, true) as $i => $val ) {
                $path   = $val;
                if ( !Storage::isFile($from.$path) ) continue;
                $info       = pathinfo($path);
                $dirname    = empty($info['dirname']) ? '' : $info['dirname'].'/';
                Storage::makeDirectory($to.$dirname);
                Storage::copy($from.$path, $to.$path);
            }
        }
    }

    function copyUnitArchives($from, $to, $preApproval=false)
    {
        //------
        // Unit
        $DB = DB::singleton(dsn());

        $_RVID = RVID;
        if ( $preApproval ) {
            $_RVID = 1;
        }
        if ( $_RVID ) {
            $SQL    = SQL::newSelect('column_rev');
            $SQL->addWhereOpr('column_rev_id', $_RVID);
        } else {
            $SQL    = SQL::newSelect('column');
        }
        $SQL->addWhereOpr('column_entry_id', EID);
        $SQL->addWhereOpr('column_blog_id', BID);
        $q = $SQL->get(dsn());

        if ( $DB->query($q, 'fetch') and ($row = $DB->fetch($q)) ) { do {
            $type = detectUnitTypeSpecifier($row['column_type']);
            switch ( $type ) {
                case 'image':
                    if ( empty($row['column_field_2']) ) break;
                    $oldAry = explodeUnitData($row['column_field_2']);
                    foreach ( $oldAry as $old ) {
                        $info   = pathinfo($old);
                        $dirname= empty($info['dirname']) ? '' : $info['dirname'].'/';
                        Storage::makeDirectory($to.$dirname);

                        $path   = $from.$old;
                        $large  = otherSizeImagePath($path, 'large');
                        $tiny   = otherSizeImagePath($path, 'tiny');
                        $square = otherSizeImagePath($path, 'square');

                        $newPath    = $to.$old;
                        $newLarge   = otherSizeImagePath($newPath, 'large');
                        $newTiny    = otherSizeImagePath($newPath, 'tiny');
                        $newSquare  = otherSizeImagePath($newPath, 'square');

                        copyFile($path, $newPath);
                        copyFile($large, $newLarge);
                        copyFile($tiny, $newTiny);
                        copyFile($square, $newSquare);
                    }
                    break;
                case 'file':
                    if ( empty($row['column_field_2']) ) break;
                    $oldAry = explodeUnitData($row['column_field_2']);
                    foreach ( $oldAry as $old ) {
                        $info   = pathinfo($old);
                        $dirname= empty($info['dirname']) ? '' : $info['dirname'].'/';
                        Storage::makeDirectory($to.$dirname);

                        $path   = $from.$old;
                        $newPath    = $to.$old;

                        copyFile($path, $newPath);
                    }
                    break;
                case 'custom':
                    if ( empty($row['column_field_6']) ) break;
                    $Field = acmsUnserialize($row['column_field_6']);
                    foreach ( $Field->listFields() as $fd ) {
                        if ( 1
                            && !strpos($fd, '@path')
                            && !strpos($fd, '@tinyPath')
                            && !strpos($fd, '@largePath')
                            && !strpos($fd, '@squarePath')
                        ) {
                            continue;
                        }
                        foreach ( $Field->getArray($fd, true) as $i => $old ) {
                            $info       = pathinfo($old);
                            $dirname    = empty($info['dirname']) ? '' : $info['dirname'].'/';
                            Storage::makeDirectory($to.$dirname);

                            $path = $from.$old;
                            $newPath = $to.$old;

                            copyFile($path, $newPath);
                        }
                    }
                    break;
            }
        } while ( $row = $DB->fetch($q) ); }
    }

    function update($exceptField=false)
    {
        ACMS_RAM::entry(EID, null);

        $DB     = DB::singleton(dsn());
        $Entry  = $this->extract('entry');
        $Geo    = $this->extract('geometry');
        $this->fix($Entry);
        $CustomFieldCollection = array();

        //-------------------------------------
        // 公開済み or 承認前 エントリー
        // $preApproval     => 承認前エントリー
        // $updateApproval  => 承認済エントリー
        $preEntry       = ACMS_RAM::entry(EID);
        $approvalStatus = $preEntry['entry_approval'];
        $preApproval    = enableApproval() && $approvalStatus === 'pre_approval';
        $updateApproval = enableApproval() && $approvalStatus !== 'pre_approval';

        if ( sessionWithApprovalAdministrator() ) {
            $preApproval    = false;
            $updateApproval = false;
        }

        //----------------------------
        // 未来バージョンとして保存フラグ
        $revisionDraft = false;
        if ( 1
            && enableRevision(false)
            && $Entry->get('revision_type') == 'draft_revision'
        ) {
            $revisionDraft = true;
        }

        //------
        // cid
        if ( !($cid = $Entry->get('category_id')) ) $cid = null;
        $Entry->setMethod('status', 'required');
        $Entry->setMethod('status', 'in', array('open', 'close', 'draft', 'trash'));
        $Entry->setMethod('status', 'category', true);
//        $Entry->setMethod('status', 'category', is_null($cid) ? true : !(1
//            and 'close' == ACMS_RAM::categoryStatus($cid)
//            and 'open'  == $Entry->get('status')
//        ));
        $Entry->setMethod('title', 'required');
        if ( !!($code = strval($Entry->get('code'))) ) {
            if ( !config('entry_code_extension') ) {
                $Entry->setMethod('code', 'reserved', !isReserved($code, false));
            }
            if ( config('check_duplicate_entry_code') === 'on'  ) {
                $Entry->setMethod('code', 'double', !Entry::validEntryCodeDouble($code, BID, $cid, EID));
            }
        }

        $Entry->setMethod('code', 'string', isValidCode($Entry->get('code')));
        $Entry->setMethod('indexing', 'required');
        $Entry->setMethod('indexing', 'in', array('on', 'off'));
        $Entry->setMethod('entry', 'operable', $this->isOperable());
        $Entry = Entry::validTag($Entry);
        $Entry->validate(new ACMS_Validator());

        //------------------------------------
        // カスタムフィールドファイルを一時的にコピー
        $moveFieldArchive = false;
        // リビジョンから変更
        if ( RVID && !$updateApproval ) {
            $this->copyFieldArchives(REVISON_ARCHIVES_DIR, ARCHIVES_DIR.'TEMP/');
            $moveFieldArchive = ($revisionDraft) ? 'REVISON_ARCHIVES_DIR' : 'ARCHIVES_DIR';
        // 公開エントリから下書き
        } else if ( $revisionDraft ) {
            $this->copyFieldArchives(ARCHIVES_DIR, ARCHIVES_DIR.'TEMP/');
            $moveFieldArchive = 'REVISON_ARCHIVES_DIR';
        // 承認機能 ON まだエントリが存在しない場合
        } else if ( $preApproval ) {
            $this->copyFieldArchives(REVISON_ARCHIVES_DIR, ARCHIVES_DIR, true);
        // 承認機能 ON エントリ存在
        } else if ( !RVID && $updateApproval ) {
            $this->copyFieldArchives(ARCHIVES_DIR, ARCHIVES_DIR.'TEMP/');
            $moveFieldArchive = 'REVISON_ARCHIVES_DIR';
        // 承認機能 ON リビジョンから変更
        } else if ( RVID && $updateApproval ) {
            $this->copyFieldArchives(REVISON_ARCHIVES_DIR, ARCHIVES_DIR.'TEMP/');
            $moveFieldArchive = 'REVISON_ARCHIVES_DIR';
        }

        //--------------
        // custom field
        $deleteField = new Field();
        $Field  = $this->extract('field', new ACMS_Validator(), $deleteField, $moveFieldArchive);
        foreach ( $this->fieldNames as $fieldName ) {
            $CustomFieldCollection[$fieldName] = $this->extract($fieldName, new ACMS_Validator());
        }

        //-------
        // entry
        $range  = strval($Entry->get('summary_range'));
        $range  = ('' === $range) ? null : intval($range);

        $moveArchives = false;
        if ( !$this->Post->isValidAll() ) {
            if ($Field->isValid('recover_acms_Po9H2zdPW4fj', 'required')) {
                $this->addMessage('failure');
            }
            $Column = Entry::extractColumn($range, false); // old画像を削除しない
            $range = Entry::getSummaryRange();
            $this->Post->set('step', 'reapply');
            $this->Post->set('action', 'update');
            $this->Post->set('column', acmsSerialize($Column));
            return false;
        } else {
            //-----------------------------
            // ユニットファイルを一時的にコピー
            // リビジョンから変更
            if ( RVID && !$updateApproval ) {
                $this->copyUnitArchives(REVISON_ARCHIVES_DIR, ARCHIVES_DIR.'TEMP/');
                $moveArchives = ($revisionDraft) ? 'REVISON_ARCHIVES_DIR' : 'ARCHIVES_DIR';
            // 公開エントリから下書き
            } else if ( $revisionDraft ) {
                $this->copyUnitArchives(ARCHIVES_DIR, ARCHIVES_DIR.'TEMP/');
                $moveArchives = 'REVISON_ARCHIVES_DIR';
            // 承認機能 ON まだエントリが存在しない場合
            } else if ( $preApproval ) {
                $this->copyUnitArchives(REVISON_ARCHIVES_DIR, ARCHIVES_DIR, true);
            // 承認機能 ON エントリ存在
            } else if ( !RVID && $updateApproval ) {
                $this->copyUnitArchives(ARCHIVES_DIR, ARCHIVES_DIR.'TEMP/');
                $moveArchives = 'REVISON_ARCHIVES_DIR';
            // 承認機能 ON リビジョンから変更
            } else if ( RVID && $updateApproval ) {
                $this->copyUnitArchives(REVISON_ARCHIVES_DIR, ARCHIVES_DIR.'TEMP/');
                $moveArchives = 'REVISON_ARCHIVES_DIR';
            }

            //-----------------------------
            // ユニットの整形＆ファイル類の生成
            $Column = Entry::extractColumn($range, true, false, $moveArchives);
            $range = Entry::getSummaryRange();
        }

        //--------
        // column
        $primaryImageId = 0;
        // 下書きバージョンでない場合
        if ( !$revisionDraft && !$updateApproval ) {
            $Res = $this->saveColumn($Column, EID, BID, false, RVID, $moveArchives);
            $Column = Entry::getSavedColumn();
            $primaryImageId = empty($Res) ? null : (
                !$Entry->get('primary_image') ? reset($Res) : (
                    !empty($Res[$Entry->get('primary_image')]) ? $Res[$Entry->get('primary_image')] : reset($Res)
                )
            );
        }

        //-------------
        // title, code
        $title  = $Entry->get('title');
        $code   = trim(strval($Entry->get('code')), '/');
        if ( !empty($code) and !!config('entry_code_extension') and !strpos($code, '.') ) $code .= ('.'.config('entry_code_extension'));

        //--------------------------------------
        // status, start_datetime, end_datetime
        $status = $Entry->get('status');
        $datetime   = ( 'open' == $status and 'draft' == ACMS_RAM::entryStatus(EID) and config('update_datetime_as_entry_open') !== 'off' ) ?
            date('Y-m-d H:i:s', REQUEST_TIME) : $Entry->get('date').' '.$Entry->get('time')
        ;

        $SQL    = SQL::newUpdate('entry');
        $row    = array(
            'entry_category_id'     => $cid,
            'entry_code'            => $code,
            'entry_summary_range'   => $range,
            'entry_status'          => $status,
            'entry_title'           => $title,
            'entry_link'            => strval($Entry->get('link')),
            'entry_datetime'        => $datetime,
            'entry_start_datetime'  => $this->getFixPublicDate($Entry, $datetime),
            'entry_end_datetime'    => $Entry->get('end_date').' '.$Entry->get('end_time'),
            'entry_indexing'        => $Entry->get('indexing', 'on'),
            'entry_primary_image'   => $primaryImageId,
            'entry_updated_datetime'=> date('Y-m-d H:i:s', REQUEST_TIME),
        );

        if ( !$preApproval ) {
            $row['entry_approval']  = 'none';
        }

        //-------------------------
        // 下書きバージョンでない場合
        if ( !$revisionDraft && !$updateApproval ) {
            foreach ( $row as $key => $val ) $SQL->addUpdate($key, $val);
            $SQL->addWhereOpr('entry_id', EID);
            $SQL->addWhereOpr('entry_blog_id', BID);
            $DB->query($SQL->get(dsn()), 'exec');

            $SQL    = SQL::newSelect('entry');
            $SQL->addWhereOpr('entry_id', EID);
            $SQL->addWhereOpr('entry_blog_id', BID);
            ACMS_RAM::entry(EID, $DB->query($SQL->get(dsn()),'row'));

            //-----
            // tag
            $SQL    = SQL::newDelete('tag');
            $SQL->addWhereOpr('tag_entry_id', EID);
            $DB->query($SQL->get(dsn()), 'exec');
            $tags = $Entry->get('tag');
            if (!empty($tags)) {
                $tags = Common::getTagsFromString($tags);
                foreach ( $tags as $sort => $tag ) {
                    if ( isReserved($tag) ) continue;
                    $SQL = SQL::newInsert('tag');
                    $SQL->addInsert('tag_name', $tag);
                    $SQL->addInsert('tag_sort', $sort + 1);
                    $SQL->addInsert('tag_entry_id', EID);
                    $SQL->addInsert('tag_blog_id', BID);
                    $DB->query($SQL->get(dsn()), 'exec');
                }
            }

            //---------------
            // related entry
            Entry::saveRelatedEntries(EID, $Entry->getArray('related'), null, $Entry->getArray('related_type'), $Entry->getArray('loaded_realted_entries'));

            //--------------
            // sub category
            Entry::saveSubCategory(EID, $cid, $Entry->get('sub_category_id'));

            //----------
            // geometry
            $this->saveGeometry('eid', EID, $this->extract('geometry'));

            if ( !$exceptField ) {
                //-------
                // field
                Common::saveField('eid', EID, $Field, null, RVID, $moveFieldArchive);

                //--------------
                // custom field
                foreach ( $CustomFieldCollection as $fieldName => $customField ) {
                    $this->saveCustomField($fieldName, EID, $customField);
                }
            }

            //----------
            // fulltext
            Common::saveFulltext('eid', EID, Common::loadEntryFulltext(EID));

            //-----------
            // trackback
            if ( $endpoint = $Entry->get('trackback_url') ) {
                Entry::pingTrackback($endpoint, EID);
            }
        }

        //-----------------
        // create revision
        $rvid = 0;
        if ( enableRevision(false) && get_called_class() !== 'ACMS_POST_Entry_Update_Detail' ) {
            $rvid = Entry::saveEntryRevision(EID, $row, $Entry->get('revision_type'), $Entry->get('revision_memo'));
            if ( $revisionDraft || $updateApproval ) {
                $Res = Entry::saveUnitRevision($Column, EID, BID, $rvid, $moveArchives);
                Entry::getSavedColumn();
            } else {
                $Res = Entry::saveUnitRevision($Column, EID, BID, $rvid);
                Entry::getSavedColumn();
            }
            $primaryImageId = empty($Res) ? null : (
                !$Entry->get('primary_image') ? reset($Res) : (
                    !empty($Res[$Entry->get('primary_image')]) ? $Res[$Entry->get('primary_image')] : reset($Res)
                )
            );

            if ( $revisionDraft || $updateApproval ) {
                // primaryImageIdを更新
                $SQL = SQL::newUpdate('entry_rev');
                $SQL->addUpdate('entry_primary_image', $primaryImageId);
                $SQL->addWhereOpr('entry_id', EID);
                $SQL->addWhereOpr('entry_rev_id', $rvid);
                $SQL->addWhereOpr('entry_blog_id', BID);
                $DB->query($SQL->get(dsn()), 'exec');

                Entry::saveFieldRevision(EID, $Field, $rvid, $moveFieldArchive);
            } else {
                Entry::saveFieldRevision(EID, $Field, $rvid);
            }

            $tags       = $Entry->get('tag');
            $SQL    = SQL::newDelete('tag_rev');
            $SQL->addWhereOpr('tag_entry_id', EID);
            $SQL->addWhereOpr('tag_rev_id', $rvid);
            $DB->query($SQL->get(dsn()), 'exec');
            if (!empty($tags)) {
                $tags = Common::getTagsFromString($tags);
                foreach ($tags as $sort => $tag) {
                    $SQL = SQL::newInsert('tag_rev');
                    $SQL->addInsert('tag_name', $tag);
                    $SQL->addInsert('tag_sort', $sort + 1);
                    $SQL->addInsert('tag_entry_id', EID);
                    $SQL->addInsert('tag_blog_id', BID);
                    $SQL->addInsert('tag_rev_id', $rvid);
                    $DB->query($SQL->get(dsn()), 'exec');
                }
            }

            //---------------
            // related entry
            Entry::saveRelatedEntries(EID, $Entry->getArray('related'), $rvid, $Entry->getArray('related_type'), $Entry->getArray('loaded_realted_entries'));

            //--------------
            // sub category
            Entry::saveSubCategory(EID, $cid, $Entry->get('sub_category_id'), BID, $rvid);

            //----------
            // geometry
            $this->saveGeometry('eid', EID, $this->extract('geometry'), $rvid);

            // エントリのカレントリビジョンを変更
            if ( !$revisionDraft && ( !enableApproval() || sessionWithApprovalAdministrator() ) ) {
                $SQL    = SQL::newUpdate('entry');
                $SQL->addUpdate('entry_current_rev_id', $rvid);
                $SQL->addWhereOpr('entry_id', EID);
                $SQL->addWhereOpr('entry_blog_id', BID);
                $DB->query($SQL->get(dsn()), 'exec');
            }
            deleteDirectory(ARCHIVES_DIR.'TEMP/');

            if ( $preApproval ) {
                Entry::entryArchivesDelete(EID);
            }
        }

        if ( $revisionDraft || $updateApproval ) {
            $cid = ACMS_RAM::entryCategory(EID);
        }

        //-------------------
        // キャッシュクリア予約
        Entry::updateCacheControl($row['entry_start_datetime'], $row['entry_end_datetime'], BID, EID);

        $SQL = SQL::newSelect('entry');
        $SQL->addSelect('entry_status');
        $SQL->addWhereOpr('entry_id', EID);
        $SQL->addWhereOpr('entry_blog_id', BID);
        $status = $DB->query($SQL->get(dsn()), 'one');

        //----------------
        // キャッシュクリア
        ACMS_POST_Cache::clearEntryPageCache(EID);

        //------
        // Hook
        if (HOOK_ENABLE) {
            $Hook = ACMS_Hook::singleton();
            $Hook->call('saveEntry', array(EID, $rvid));
            $events = array('entry:updated');
            if (1
                && !$revisionDraft
                && !$updateApproval
                && $preEntry['entry_status'] !== 'open'
                && $status === 'open'
                && strtotime($row['entry_start_datetime']) <= REQUEST_TIME
                && strtotime($row['entry_end_datetime']) >= REQUEST_TIME
            ) {
                $events[] = 'entry:opened';
            }
            Webhook::call(BID, 'entry', $events, array(EID, $rvid));
        }

        return array(
            'eid' => EID,
            'cid' => $cid,
            'ecd' => $code,
            'ccd' => ACMS_RAM::categoryCode($cid),
            'rvid' => $rvid,
            'trash' => $status,
            'updateApproval' => $updateApproval,
            'revisionDraft' => $revisionDraft,
            'success' => 1,
        );
    }

    function post()
    {
        if (!Entry::validateMediaUnit()) {
            httpStatusCode('500 Internal Server Error');
            return;
        }
        $updatedResponse = $this->update();

        // nextstep周りの実装は、プレビューが会った頃の古いコード
        // resultのみ機能しているが、confirmをしてもプレビューにならない（即時保存されている）

        $redirect   = $this->Post->get('redirect');
        $nextstep   = $this->Post->get('nextstep');
        $backend    = $this->Post->get('backend');
        $ajax       = $this->Post->get('ajaxUploadImageAccess') === 'true';

        setCookieDelFlag();
        $this->clearCache(BID, EID);

        if (is_array($updatedResponse) && !empty($redirect) && Common::isSafeUrl($redirect)) {
            $this->responseRedirect($redirect, $ajax);
        } else if ( !empty($nextstep) ) {
            $this->Post->set('step', $nextstep);
            $this->Post->set('action', 'update');
            $this->Post->set('column', acmsSerialize(Entry::extractColumn()));
            return $this->responseGet($ajax);
        } else if ( is_array($updatedResponse) ) {
            $Session =& Field::singleton('session');
            $Session->add('entry_action', 'update');
            $info = array(
                'bid'   => BID,
                'cid'   => $updatedResponse['cid'],
                'eid'   => EID,
            );
            if ( $updatedResponse['trash'] == 'trash' ) {
                $info['query'] = array('trash' => 'show');
            }
            if (!empty($backend)) {
                $query = array('success' => $updatedResponse['success']);
                if ($updatedResponse['rvid'] && $updatedResponse['updateApproval']) {
                    $query['rvid'] = $updatedResponse['rvid'];
                }
                $redirect = acmsLink(array(
                    'bid'   => BID,
                    'cid'   => $updatedResponse['cid'],
                    'eid'   => EID,
                    'admin' => 'entry_editor',
                    'query' => $query,
                ));
                $this->responseRedirect($redirect, $ajax);
            }
            $this->responseRedirect(acmsLink($info), $ajax);
        } else {
            return $this->responseGet($ajax);
        }
    }

    function isOperable()
    {
        if ( !EID ) return false;
        if ( !IS_LICENSED ) return false;

        if ( roleAvailableUser() ) {
            if ( !roleAuthorization('entry_edit', BID, EID) ) return false;
        } else {
            if ( !sessionWithCompilation(BID, false) ) {
                if ( !sessionWithContribution(BID, false) ) return false;
                if ( SUID <> ACMS_RAM::entryUser(EID) && (config('approval_contributor_edit_auth') === 'on' || !enableApproval(BID, CID)) ) return false;
            }
        }
        return true;
    }
}
