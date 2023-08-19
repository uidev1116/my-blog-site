<?php

class ACMS_POST_Entry_Insert extends ACMS_POST_Entry
{
    /**
     * @var array
     */
    var $fieldNames  = array ();

    /**
     * @see ACMS_User_POST_EntryExtendSample_Insert
     *
     * @param string            $fieldName
     * @param int               $eid
     * @param Field_Validation  $Field
     * @return void
     */
    protected function saveCustomField($fieldName, $eid, $Field)
    {

    }

    /**
     * get code
     *
     * @param \Field $entry
     * @param int $eid
     * @return string
     */
    protected function getCode($entry, $eid)
    {
        $title = $entry->get('title');
        $code = trim(strval($entry->get('code')), '/');
        if ( empty($code) ) {
            $code   = ('on' == config('entry_code_title')) ? stripWhitespace($title) : config('entry_code_prefix').$eid;
        }
        if ( !!config('entry_code_extension') and !strpos($code, '.') ) $code .= ('.'.config('entry_code_extension'));

        return $code;
    }

    /**
     * @return array
     */
    protected function insert()
    {
        $DB     = DB::singleton(dsn());
        $Entry  = $this->extract('entry');
        $this->fix($Entry);
        $CustomFieldCollection = array();
        $eid = $DB->query(SQL::nextval('entry_id', dsn()), 'seq');

        //-----
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
        $code = strval($Entry->get('code'));
        if ( empty($code) ) {
            $code = $this->getCode($Entry, $eid);
        }
        if ( !config('entry_code_extension') ) {
            $Entry->setMethod('code', 'reserved', !isReserved($code, false));
        }
        if ( config('check_duplicate_entry_code') === 'on'  ) {
            $Entry->setMethod('code', 'double', !Entry::validEntryCodeDouble($code, BID, $cid));
        }
        $Entry->setMethod('code', 'string', isValidCode($Entry->get('code')));
        $Entry->setMethod('indexing', 'required');
        $Entry->setMethod('indexing', 'in', array('on', 'off'));

        if ( roleAvailableUser() ) {
            $Entry->setMethod('entry', 'operable', 1
                and IS_LICENSED
                and roleAuthorization('entry_edit', BID)
            );
        } else {
            $Entry->setMethod('entry', 'operable', 1
                and IS_LICENSED
                and sessionWithContribution(BID)
            );
        }
        $Entry = Entry::validTag($Entry);

        $Entry->validate(new ACMS_Validator());

        //--------------
        // custom field
        $Field  = $this->extract('field', new ACMS_Validator());
        foreach ( $this->fieldNames as $fieldName ) {
            $CustomFieldCollection[$fieldName] = $this->extract($fieldName);
        }

        //-------
        // entry
        $range = strval($Entry->get('summary_range'));
        $range = ('' === $range) ? null : intval($range);

        $Column = Entry::extractColumn($range);
        $range = Entry::getSummaryRange();

        if ( !$this->Post->isValidAll() ) {
            if ($Field->isValid('recover_acms_Po9H2zdPW4fj', 'required')) {
                $this->addMessage('failure');
            }
            $this->Post->set('step', 'reapply');
            $this->Post->set('action', 'insert');
            $this->Post->set('column', acmsSerialize($Column));
            return false;
        }

        //--------
        // column
        $Res = Entry::saveColumn($Column, $eid, BID);
        $Column = Entry::getSavedColumn();
        $primaryImageId = empty($Res) ? null : (
            !$Entry->get('primary_image') ? reset($Res) : (
                !empty($Res[$Entry->get('primary_image')]) ? $Res[$Entry->get('primary_image')] : reset($Res)
            )
        );

        //----------
        // geometry
        $this->saveGeometry('eid', $eid, $this->extract('geometry'), RVID);

        //---------------
        // related entry
        Entry::saveRelatedEntries($eid, $Entry->getArray('related'), RVID, $Entry->getArray('related_type'), $Entry->getArray('loaded_realted_entries'));

        //--------------
        // sub category
        Entry::saveSubCategory($eid, $cid, $Entry->get('sub_category_id'));

        //-------------
        // title, code
        $title  = $Entry->get('title');

        $SQL    = SQL::newSelect('entry');
        $SQL->setSelect('entry_sort');
        $SQL->addWhereOpr('entry_blog_id', BID);
        $SQL->setOrder('entry_sort', 'DESC');
        $SQL->setLimit(1);
        $esort  = intval($DB->query($SQL->get(dsn()), 'one')) + 1;

        $SQL    = SQL::newSelect('entry');
        $SQL->setSelect('entry_user_sort');
        $SQL->addWhereOpr('entry_user_id', SUID);
        $SQL->addWhereOpr('entry_blog_id', BID);
        $SQL->setOrder('entry_user_sort', 'DESC');
        $SQL->setLimit(1);
        $usort  = intval($DB->query($SQL->get(dsn()), 'one')) + 1;

        $SQL    = SQL::newSelect('entry');
        $SQL->setSelect('entry_category_sort');
        $SQL->addWhereOpr('entry_category_id', $cid);
        $SQL->addWhereOpr('entry_blog_id', BID);
        $SQL->setOrder('entry_category_sort', 'DESC');
        $SQL->setLimit(1);
        $csort  = intval($DB->query($SQL->get(dsn()), 'one')) + 1;

        $SQL    = SQL::newInsert('entry');
        $datetime = $Entry->get('date') . ' ' . $Entry->get('time');
        $row    = array(
            'entry_id'              => $eid,
            'entry_posted_datetime' => date('Y-m-d H:i:s', REQUEST_TIME),
            'entry_updated_datetime'=> date('Y-m-d H:i:s', REQUEST_TIME),
            'entry_category_id'     => $cid,
            'entry_user_id'         => SUID,
            'entry_blog_id'         => BID,
            'entry_code'            => $code,
            'entry_summary_range'   => $range,
            'entry_sort'            => $esort,
            'entry_user_sort'       => $usort,
            'entry_category_sort'   => $csort,
            'entry_status'          => $Entry->get('status'),
            'entry_title'           => $title,
            'entry_link'            => strval($Entry->get('link')),
            'entry_datetime'        => $datetime,
            'entry_start_datetime'  => $this->getFixPublicDate($Entry, $datetime),
            'entry_end_datetime'    => $Entry->get('end_date').' '.$Entry->get('end_time'),
            'entry_indexing'        => $Entry->get('indexing'),
            'entry_primary_image'   => $primaryImageId,
            'entry_hash'            => md5(SYSTEM_GENERATED_DATETIME.date('Y-m-d H:i:s', REQUEST_TIME)),
            'entry_current_rev_id'  => enableApproval() ? 0 : 1,
            'entry_last_update_user_id' => SUID,
        );

        foreach ( $row as $key => $val ) $SQL->addInsert($key, $val);
        $DB->query($SQL->get(dsn()), 'exec');
        ACMS_RAM::entry($eid, $row);

        //-----
        // tag
        $tags   = $Entry->get('tag');
        if (!empty($tags)) {
            $tags = Common::getTagsFromString($tags);
            foreach ( $tags as $sort => $tag ) {
                $SQL    = SQL::newInsert('tag');
                $SQL->addInsert('tag_name', $tag);
                $SQL->addInsert('tag_sort', $sort + 1);
                $SQL->addInsert('tag_entry_id', $eid);
                $SQL->addInsert('tag_blog_id', BID);
                $DB->query($SQL->get(dsn()), 'exec');
            }
        }

         //---------------
        // related entry
        Entry::saveRelatedEntries($eid, $Entry->getArray('related'), null, $Entry->getArray('related_type'), $Entry->getArray('loaded_realted_entries'));

        //------
        // field
        Common::saveField('eid', $eid, $Field);

        //-----------------
        // create revision
        $rvid = 0;
        if ( enableRevision() ) {
            $rvid = Entry::saveEntryRevision($eid, $row, null, $Entry->get('revision_memo'));
            $Res = Entry::saveUnitRevision($Column, $eid, BID, $rvid);
            Entry::getSavedColumn();

            // primaryImageIdを更新
            $primaryImageId = empty($Res) ? null : (
                !$Entry->get('primary_image') ? reset($Res) : (
                    !empty($Res[$Entry->get('primary_image')]) ? $Res[$Entry->get('primary_image')] : reset($Res)
                )
            );
            $SQL = SQL::newUpdate('entry_rev');
            $SQL->addUpdate('entry_primary_image', $primaryImageId);
            $SQL->addWhereOpr('entry_id', $eid);
            $SQL->addWhereOpr('entry_rev_id', $rvid);
            $SQL->addWhereOpr('entry_blog_id', BID);
            $DB->query($SQL->get(dsn()), 'exec');

            Entry::saveFieldRevision($eid, $Field, $rvid);
            $tags = $Entry->get('tag');
            if (!empty($tags)) {
                $tags = Common::getTagsFromString($tags);
                foreach ( $tags as $sort => $tag ) {
                    $SQL    = SQL::newInsert('tag_rev');
                    $SQL->addInsert('tag_name', $tag);
                    $SQL->addInsert('tag_sort', $sort + 1);
                    $SQL->addInsert('tag_entry_id', $eid);
                    $SQL->addInsert('tag_blog_id', BID);
                    $SQL->addInsert('tag_rev_id', 1);
                    $DB->query($SQL->get(dsn()), 'exec');
                }
            }

            //---------------
            // related entry
            Entry::saveRelatedEntries($eid, $Entry->getArray('related'), $rvid, $Entry->getArray('related_type'), $Entry->getArray('loaded_realted_entries'));

            //--------------
            // sub category
            Entry::saveSubCategory($eid, $cid, $Entry->get('sub_category_id'), BID, $rvid);
        }
        if ( enableApproval() && !sessionWithApprovalAdministrator() ) {
            $SQL    = SQL::newUpdate('entry');
            $SQL->addUpdate('entry_approval', 'pre_approval');
            $SQL->addWhereOpr('entry_id', $eid);
            $SQL->addWhereOpr('entry_blog_id', BID);
            $DB->query($SQL->get(dsn()), 'exec');
            ACMS_RAM::entry($eid, null);

            Entry::entryArchivesDelete($eid);
        }

        //--------------
        // custom field
        foreach ( $CustomFieldCollection as $fieldName => $customField ) {
            $this->saveCustomField($fieldName, $eid, $customField);
        }

        //----------
        // fulltext
        Common::saveFulltext('eid', $eid, Common::loadEntryFulltext($eid));

        //-----------
        // trackback
        if ( $endpoint = $Entry->get('trackback_url') ) {
            Entry::pingTrackback($endpoint, $eid);
        }

        //-------------------
        // キャッシュクリア予約
        Entry::updateCacheControl($row['entry_start_datetime'], $row['entry_end_datetime'], BID, $eid);

        //------
        // Hook
        if (HOOK_ENABLE) {
            $Hook = ACMS_Hook::singleton();
            $Hook->call('saveEntry', array(intval($eid), null));
            $events = array('entry:created');
            if (1
                && !(enableApproval() && !sessionWithApprovalAdministrator())
                && $row['entry_status'] === 'open'
                && strtotime($row['entry_start_datetime']) <= REQUEST_TIME
                && strtotime($row['entry_end_datetime']) >= REQUEST_TIME
            ) {
                $events[] = 'entry:opened';
            }
            Webhook::call(BID, 'entry', $events, array(intval($eid), null));
        }

        return array(
            'eid' => $eid,
            'cid' => $cid,
            'ecd' => $code,
            'ccd' => ACMS_RAM::categoryCode($cid),
            'trash' => $Entry->get('status'),
            'success' => 1,
        );
    }

    function post()
    {
        $insertedResponse = $this->insert();

        // nextstep周りの実装は、プレビューが会った頃の古いコード
        // resultのみ機能しているが、confirmをしてもプレビューにならない（即時保存されている）

        $redirect   = $this->Post->get('redirect');
        $nextstep   = $this->Post->get('nextstep');
        $backend    = $this->Post->get('backend');
        $ajax       = $this->Post->get('ajaxUploadImageAccess') === 'true';

        setCookieDelFlag();
        $this->clearCache(BID, EID);

        if (is_array($insertedResponse) && !empty($redirect) && Common::isSafeUrl($redirect)) {
            $this->responseRedirect($redirect, $ajax);
        }
        else if ( !empty($nextstep) ) {
            $this->Post->set('step', $nextstep);
            $this->Post->set('action', 'insert');
            $this->Post->set('column', acmsSerialize(Entry::extractColumn()));
            return $this->responseGet($ajax);
        }
        else if ( is_array($insertedResponse) ) {
            $Session =& Field::singleton('session');
            $Session->add('entry_action', 'update');
            $info   = array(
                'bid'   => BID,
                'cid'   => $insertedResponse['cid'],
                'eid'   => $insertedResponse['eid'],
                'query' => array(),
            );
            if ( $insertedResponse['trash'] == 'trash' ) {
                    $info['query'] = array('trash' => 'show');
            }
            if ( !empty($backend) ) {
                $info['admin'] = 'entry_editor';
                $info['query'] += array('success' => $insertedResponse['success']);
            }
            $this->responseRedirect(acmsLink($info), $ajax);
        } else {
            return $this->responseGet($ajax);
        }
    }
}
