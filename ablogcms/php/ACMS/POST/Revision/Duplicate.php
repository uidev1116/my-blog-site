<?php

class ACMS_POST_Revision_Duplicate extends ACMS_POST_Entry
{
    function post()
    {
        try {
            if (roleAvailableUser()) {
                if (!roleAuthorization('entry_edit', BID, EID)) {
                    throw new \RuntimeException ('権限がありません');
                }
            } else {
                if (!sessionWithCompilation(BID, false)) {
                    if (!sessionWithContribution(BID, false)) {
                        throw new \RuntimeException ('権限がありません');
                    }
                    if (SUID <> ACMS_RAM::entryUser(EID) && !enableApproval(BID, CID)) {
                        throw new \RuntimeException ('権限がありません');
                    }
                }
            }
            $DB = DB::singleton(dsn());

            // 新規リビジョン番号取得
            $SQL = SQL::newSelect('entry_rev');
            $SQL->addSelect('entry_rev_id', 'max_rev_id', null, 'MAX');
            $SQL->addWhereOpr('entry_id', EID);
            $SQL->addWhereOpr('entry_blog_id', BID);

            $rvid = 2;
            if ( $max = $DB->query($SQL->get(dsn()), 'one') ) {
                $rvid = $max + 1;
            }

            $revisionName = $this->Post->get('revisionName');
            if ( empty($revisionName) ) {
                $revisionName = sprintf(config('revision_default_memo'), $rvid);
            }

            $this->entryDupe($rvid, $revisionName);
            $this->subCategoryDupe($rvid);
            $this->unitDupe($rvid);
            $this->fieldDupe($rvid);
            $this->tagsDupe($rvid);
            $this->relationDupe($rvid);

            AcmsLogger::info('「' . ACMS_RAM::entryTitle(EID) . '」エントリの作業領域からバージョンを作成しました', [
                'eid' => EID,
                'rvid' => $rvid,
                'versionName' => $revisionName,
            ]);

            if ( $this->Post->get('redirect', '') == 'approval' ) {
                $this->redirect(acmsLink(array(
                    'bid'   => BID,
                    'eid'   => EID,
                    'tpl'   => 'ajax/revision-preview.html',
                    'query' => array(
                        'rvid'  => $rvid,
                    ),
                )));
            } else {
                $this->redirect(acmsLink(array(
                    'bid'   => BID,
                    'eid'   => EID,
                    'tpl'   => 'ajax/revision-index-list.html',
                )));
            }
        } catch (\Exception $e) {
            AcmsLogger::info('「' . ACMS_RAM::entryTitle(EID) . '」エントリの作業領域からバージョンを作成できませんでした。' . $e->getMessage(), Common::exceptionArray($e));
        }
    }

    function entryDupe($rvid, $revisionName)
    {
        $DB     = DB::singleton(dsn());

        $SQL    = SQL::newSelect('entry_rev');
        $SQL->addWhereOpr('entry_id', EID);
        $SQL->addWhereOpr('entry_rev_id', 1);
        $SQL->addWhereOpr('entry_blog_id', BID);
        $q      = $SQL->get(dsn());

        $Entry  = SQL::newInsert('entry_rev');
        if ( $row = $DB->query($q, 'row') ) {
            foreach ( $row as $key => $val ) {
                if ( !in_array($key, array(
                    'entry_rev_id', 'entry_rev_status', 'entry_rev_user_id', 'entry_rev_datetime', 'entry_rev_memo'
                )) ) {
                    $Entry->addInsert($key, $val);
                }
            }
            $Entry->addInsert('entry_rev_id', $rvid);
            $Entry->addInsert('entry_rev_status', 'none');
            $Entry->addInsert('entry_rev_user_id', SUID);
            $Entry->addInsert('entry_rev_datetime', date('Y-m-d H:i:s', REQUEST_TIME));
            $Entry->addInsert('entry_rev_memo', $revisionName);
            $DB->query($Entry->get(dsn()), 'exec');
        }
    }

    function subCategoryDupe($rvid)
    {
        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('entry_sub_category_rev');
        $SQL->addWhereOpr('entry_sub_category_eid', EID);
        $SQL->addWhereOpr('entry_sub_category_rev_id', 1);
        $SQL->addWhereOpr('entry_sub_category_blog_id', BID);
        $q = $SQL->get(dsn());

        $SubCategory = SQL::newInsert('entry_sub_category_rev');
        if ($DB->query($q, 'fetch') and ($row = $DB->fetch($q))) { do {
            foreach ($row as $key => $val) {
                if ($key !== 'entry_sub_category_rev_id') {
                    $SubCategory->addInsert($key, $val);
                }
            }
            $SubCategory->addInsert('entry_sub_category_rev_id', $rvid);
            $DB->query($SubCategory->get(dsn()), 'exec');
        } while ($row = $DB->fetch($q)); }
    }

    function unitDupe($rvid)
    {
        $DB     = DB::singleton(dsn());

        $SQL    = SQL::newSelect('column_rev');
        $SQL->addWhereOpr('column_entry_id', EID);
        $SQL->addWhereOpr('column_rev_id', 1);
        $SQL->addWhereOpr('column_blog_id', BID);
        $q      = $SQL->get(dsn());

        $Unit   = SQl::newInsert('column_rev');
        if ( $DB->query($q, 'fetch') and ($row = $DB->fetch($q)) ) { do {
            $type = detectUnitTypeSpecifier($row['column_type']);
            switch ($type) {
                case 'image':
                    if ( empty($row['column_field_2']) ) break;
                    $oldAry = explodeUnitData($row['column_field_2']);
                    $newAry = array();
                    foreach ( $oldAry as $old ) {
                        $info   = pathinfo($old);
                        $dirname= empty($info['dirname']) ? '' : $info['dirname'].'/';
                        Storage::makeDirectory(ARCHIVES_DIR . $dirname);
                        $ext    = empty($info['extension']) ? '' : '.'.$info['extension'];
                        $newOld = $dirname.uniqueString().$ext;

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
                    break;
                case 'file':
                    if ( empty($row['column_field_2']) ) break;
                    $oldAry = explodeUnitData($row['column_field_2']);
                    $newAry = array();
                    foreach ( $oldAry as $old ) {
                        $info   = pathinfo($old);
                        $dirname= empty($info['dirname']) ? '' : $info['dirname'].'/';
                        Storage::makeDirectory(ARCHIVES_DIR . $dirname);
                        $ext    = empty($info['extension']) ? '' : '.'.$info['extension'];
                        $newOld = $dirname.uniqueString().$ext;

                        $path   = ARCHIVES_DIR . $old;
                        $newPath    = ARCHIVES_DIR . $newOld;

                        copyFile($path, $newPath);

                        $newAry[]   = $newOld;
                    }

                    $row['column_field_2']  = implodeUnitData($newAry);
                    break;
                case 'custom':
                    if ( empty($row['column_field_6']) ) break;
                    $Field = acmsUnserialize($row['column_field_6']);
                    foreach ($Field->listFields() as $fd) {
                        if ( 1
                            && !strpos($fd, '@path')
                            && !strpos($fd, '@tinyPath')
                            && !strpos($fd, '@largePath')
                            && !strpos($fd, '@squarePath')
                        ) {
                            continue;
                        }
                        $set = false;
                        foreach ($Field->getArray($fd, true) as $i => $old) {
                            $info = pathinfo($old);
                            $dirname = empty($info['dirname']) ? '' : $info['dirname'] . '/';
                            Storage::makeDirectory(ARCHIVES_DIR.$dirname);

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
                    break;
            }
            foreach ( $row as $key => $val ) {
                if ( $key !== 'column_rev_id' ) {
                    $Unit->addInsert($key, $val);
                }
            }
            $Unit->addInsert('column_rev_id', $rvid);
            $DB->query($Unit->get(dsn()), 'exec');
        } while ( $row = $DB->fetch($q) ); }
    }

    function fieldDupe($rvid)
    {
        $revisionField  = loadEntryField(EID, 1);
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
                if ( !Storage::isFile(ARCHIVES_DIR . $path) ) continue;
                $info       = pathinfo($path);
                $dirname    = empty($info['dirname']) ? '' : $info['dirname'].'/';
                Storage::makeDirectory(ARCHIVES_DIR . $dirname);
                Storage::copy(ARCHIVES_DIR . $path, ARCHIVES_DIR . $path);
            }
        }
        Entry::saveFieldRevision(EID, $revisionField, $rvid);
    }

    function tagsDupe($rvid)
    {
        $DB     = DB::singleton(dsn());

        $SQL    = SQL::newSelect('tag_rev');
        $SQL->addWhereOpr('tag_entry_id', EID);
        $SQL->addWhereOpr('tag_rev_id', 1);
        $SQL->addWhereOpr('tag_blog_id', BID);
        $q      = $SQL->get(dsn());

        $Tag    = SQl::newInsert('tag_rev');
        if ( $DB->query($q, 'fetch') and ($row = $DB->fetch($q)) ) { do {
            foreach ( $row as $key => $val ) {
                if ( $key !== 'tag_rev_id' ) {
                    $Tag->addInsert($key, $val);
                }
            }
            $Tag->addInsert('tag_rev_id', $rvid);
            $DB->query($Tag->get(dsn()), 'exec');
        } while ( $row = $DB->fetch($q) ); }
    }

    function relationDupe($rvid)
    {
        $DB     = DB::singleton(dsn());

        $SQL    = SQL::newSelect('relationship_rev');
        $SQL->addWhereOpr('relation_id', EID);
        $SQL->addWhereOpr('relation_rev_id', 1);
        $all    = $DB->query($SQL->get(dsn()), 'all');

        foreach ( $all as $row ) {
            $SQL = SQL::newInsert('relationship_rev');
            $SQL->addInsert('relation_id', $row['relation_id']);
            $SQL->addInsert('relation_rev_id', $rvid);
            $SQL->addInsert('relation_eid', $row['relation_eid']);
            $SQL->addInsert('relation_type', $row['relation_type']);
            $SQL->addInsert('relation_order', $row['relation_order']);
            $DB->query($SQL->get(dsn()), 'exec');
        }
    }
}
