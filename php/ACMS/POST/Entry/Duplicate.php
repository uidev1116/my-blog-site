<?php

class ACMS_POST_Entry_Duplicate extends ACMS_POST_Entry
{
    function post()
    {
        $eid = idval($this->Post->get('eid', EID));
        if (!$this->validate($eid)) {
            die();
        }
        $newEid = $this->duplicate($eid);
        $cid    = idval($this->Post->get('cid'));
        $this->redirect(acmsLink(array(
            'bid'   => BID,
            'cid'   => $cid,
            'eid'   => $newEid,
        )));
    }

    function duplicate($eid)
    {
        $DB = DB::singleton(dsn());
        $newEid = $DB->query(SQL::nextval('entry_id', dsn()), 'seq');
        if ( enableApproval(BID, CID) && !sessionWithApprovalAdministrator(BID, CID) ) {
            $this->approvalDupe($eid, $newEid);
        } else {
            $this->dupe($eid, $newEid);
        }
        return $newEid;
    }

    function validate($eid)
    {
        if (empty($eid)) return false;
        $bid = ACMS_RAM::entryBlog($eid);
        if (roleAvailableUser()) {
            if (!roleAuthorization('entry_edit', $bid, $eid)) return false;
        } else {
            if (!sessionWithCompilation($bid, false)) {
                if (!sessionWithContribution($bid, false)) return false;
                if (SUID <> ACMS_RAM::entryUser($eid) && !enableApproval($bid, CID)) return false;
            }
        }
        return true;
    }

    function _filesDupe(& $Field, & $Old_Field, $info, $int_filedindex)
    {
        $key            = $info['name'];
        $pfx            = $info['pfx'];
        $_fd            = $info['field'];
        $dirname        = $info['dirname'];
        $newBasename    = $info['newBasename'];
        $extension      = $info['extension'];

        if ( 1
            and $path = $Old_Field->get($_fd.$key, NULL, $int_filedindex)
            and Storage::isFile(ARCHIVES_DIR.$path)
        ) {
            $newPath   = $dirname.$pfx.$newBasename.$extension;

            Storage::copy(ARCHIVES_DIR.$path, ARCHIVES_DIR.$newPath);
            if ( HOOK_ENABLE ) {
                $Hook = ACMS_Hook::singleton();
                $Hook->call('mediaCreate', ARCHIVES_DIR.$newPath);
            }

            if ( $int_filedindex == 0 ) {
                $Field->setField($_fd.$key, $newPath);
            } else {
                $Field->addField($_fd.$key, $newPath);
            }
        }
    }

    function fieldDupe(& $Field)
    {
        foreach ( $Field->listFields() as $fd ) {

            if ( preg_match('/(.*?)@path$/', $fd, $match) ) {
                $_fd    = $match[1];

                // カスタムフィールドグループ対応
                $ary_path = $Field->getArray($_fd.'@path');
                if( is_array( $ary_path ) && count( $ary_path ) > 0 ){

                    $int_filedindex = 0;
                    $Old_Field = new Field();
                    $Old_Field->set($_fd.'@path',$Field->getArray($_fd.'@path') );
                    $Old_Field->set($_fd.'@largePath',$Field->getArray($_fd.'@largePath') );
                    $Old_Field->set($_fd.'@tinyPath',$Field->getArray($_fd.'@tinyPath') );
                    $Old_Field->set($_fd.'@squarePath',$Field->getArray($_fd.'@squarePath') );

                    foreach( $ary_path as $path ){
                        if ( 1
                            and Storage::isFile(ARCHIVES_DIR.$path)
                            and preg_match('@^(.*?)([^/]+)(\.[^.]+)$@', $path, $match)
                        ) {
                            $dirname    = $match[1];
                            $extension  = $match[3];

                            $info = array(
                                'field'         => $_fd,
                                'dirname'       => $dirname,
                                'newBasename'   => uniqueString(),
                                'extension'     => $extension,
                            );

                            foreach (array(
                                         ''          => '@path',
                                         'large-'    => '@largePath',
                                         'tiny-'     => '@tinyPath',
                                         'square-'   => '@squarePath',
                                     ) as $pfx => $name) {
                                $info['name']   = $name;
                                $info['pfx']    = $pfx;
                                $this->_filesDupe($Field, $Old_Field, $info, $int_filedindex);
                            }
                        } else {
                            foreach (array(
                                         ''          => '@path',
                                         'large-'    => '@largePath',
                                         'tiny-'     => '@tinyPath',
                                         'square-'   => '@squarePath',
                                     ) as $pfx => $name) {
                                if ($int_filedindex === 0) {
                                    $Field->deleteField($_fd . $name);
                                }
                                $Field->addField($_fd . $name, '');
                            }
                        }
                        $int_filedindex++;
                    }
                }
            }
        }
    }

    function relationDupe($eid, $newEid)
    {
        $DB     = DB::singleton(dsn());

        $SQL    = SQL::newSelect('relationship');
        $SQL->addWhereOpr('relation_id', $eid);
        $all    = $DB->query($SQL->get(dsn()), 'all');

        foreach ( $all as $row ) {
            $SQL = SQL::newInsert('relationship');
            $SQL->addInsert('relation_id', $newEid);
            $SQL->addInsert('relation_eid', $row['relation_eid']);
            $SQL->addInsert('relation_type', $row['relation_type']);
            $SQL->addInsert('relation_order', $row['relation_order']);
            $DB->query($SQL->get(dsn()), 'exec');
        }
    }

    function geoDuplicate($eid, $newEid)
    {
        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('geo');
        $SQL->addWhereOpr('geo_eid', $eid);
        if ( $row = $DB->query($SQL->get(dsn()), 'row') ) {
            $SQL = SQL::newInsert('geo');
            $SQL->addInsert('geo_eid', $newEid);
            $SQL->addInsert('geo_geometry', $row['geo_geometry']);
            $SQL->addInsert('geo_zoom', $row['geo_zoom']);
            $SQL->addInsert('geo_blog_id', $row['geo_blog_id']);
            $DB->query($SQL->get(dsn()), 'exec');
        }
    }

    function approvalDupe($eid, $newEid)
    {
        $DB         = DB::singleton(dsn());
        $bid        = ACMS_RAM::entryBlog($eid);
        $approval   = ACMS_RAM::entryApproval($eid);
        $sourceDir  = ARCHIVES_DIR;
        $sourceRev  = false;

        if ( $approval === 'pre_approval' ) {
            $sourceDir  = REVISON_ARCHIVES_DIR;
            $sourceRev  = true;
        }

        //--------
        // column
        $map    = array();
        if ( $sourceRev ) {
            $SQL    = SQL::newSelect('column_rev');
            $SQL->addWhereOpr('column_rev_id', 1);
        } else {
            $SQL    = SQL::newSelect('column');
        }
        $SQL->addWhereOpr('column_entry_id', $eid);
        $SQL->addWhereOpr('column_blog_id', $bid);
        $q  = $SQL->get(dsn());
        if ( $DB->query($q, 'fetch') and ($row = $DB->fetch($q)) ) { do {
            $type = detectUnitTypeSpecifier($row['column_type']);
            switch ( $type ) {
                case 'image':
                    $oldAry = explodeUnitData($row['column_field_2']);
                    $newAry = array();
                    foreach ( $oldAry as $old ) {
                        $info   = pathinfo($old);
                        $dirname= empty($info['dirname']) ? '' : $info['dirname'].'/';
                        $ext    = empty($info['extension']) ? '' : '.'.$info['extension'];
                        $newOld = $dirname.uniqueString().$ext;
                        $path   = $sourceDir.$old;
                        $large  = otherSizeImagePath($path, 'large');
                        $tiny   = otherSizeImagePath($path, 'tiny');
                        $square = otherSizeImagePath($path, 'square');
                        $newPath    = REVISON_ARCHIVES_DIR.$newOld;
                        $newLarge   = otherSizeImagePath($newPath, 'large');
                        $newTiny    = otherSizeImagePath($newPath, 'tiny');
                        $newSquare  = otherSizeImagePath($newPath, 'square');
                        copyFile($path, $newPath);
                        copyFile($large, $newLarge);
                        copyFile($tiny, $newTiny);
                        copyFile($square, $newSquare);

                        $newAry[]   = $newOld;
                    }
                    $row['column_field_2']  = implodeUnitData($newAry);
                    break;
                case 'file':
                    $oldAry = explodeUnitData($row['column_field_2']);
                    $newAry = array();
                    foreach ( $oldAry as $old ) {
                        $old    = $row['column_field_2'];
                        $info   = pathinfo($old);
                        $dirname= empty($info['dirname']) ? '' : $info['dirname'].'/';
                        $ext    = empty($info['extension']) ? '' : '.'.$info['extension'];
                        $newOld = $dirname.uniqueString().$ext;
                        $path   = $sourceDir.$old;
                        $newPath    = REVISON_ARCHIVES_DIR.$newOld;
                        copyFile($path, $newPath);

                        $newAry[]   = $newOld;
                    }
                    $row['column_field_2']  = implodeUnitData($newAry);
                    break;
                case 'custom':
                    $Field = acmsUnserialize($row['column_field_6']);
                    foreach ( $Field->listFields() as $fd ) {
                        if ( !strpos($fd, '@path') ) {
                            continue;
                        }
                        $base = substr($fd, 0, (-1 * strlen('@path')));
                        $set = false;
                        foreach ( $Field->getArray($fd, true) as $i => $path ) {
                            if ( !Storage::isFile($sourceDir.$path) ) continue;
                            $info       = pathinfo($path);
                            $dirname    = empty($info['dirname']) ? '' : $info['dirname'].'/';
                            Storage::makeDirectory(REVISON_ARCHIVES_DIR.$dirname);
                            $ext        = empty($info['extension']) ? '' : '.'.$info['extension'];
                            $newPath    = $dirname.uniqueString().$ext;

                            $path       = $sourceDir.$path;
                            $largePath  = otherSizeImagePath($path, 'large');
                            $tinyPath   = otherSizeImagePath($path, 'tiny');
                            $squarePath = otherSizeImagePath($path, 'square');

                            $newLargePath   = otherSizeImagePath($newPath, 'large');
                            $newTinyPath    = otherSizeImagePath($newPath, 'tiny');
                            $newSquarePath  = otherSizeImagePath($newPath, 'square');

                            Storage::copy($path, REVISON_ARCHIVES_DIR.$newPath);
                            Storage::copy($largePath, REVISON_ARCHIVES_DIR.$newLargePath);
                            Storage::copy($tinyPath, REVISON_ARCHIVES_DIR.$newTinyPath);
                            Storage::copy($squarePath, REVISON_ARCHIVES_DIR.$newSquarePath);


                            if ( !$set ) {
                                $Field->delete($fd);
                                $Field->delete($base.'@largePath');
                                $Field->delete($base.'@tinyPath');
                                $Field->delete($base.'@squarePath');
                                $set = true;
                            }
                            $Field->add($fd, $newPath);
                            if ( Storage::isReadable($largePath) ) {
                                $Field->add($base.'@largePath', $newLargePath);
                            }
                            if ( Storage::isReadable($tinyPath) ) {
                                $Field->add($base.'@tinyPath', $newTinyPath);
                            }
                            if ( Storage::isReadable($squarePath) ) {
                                $Field->add($base.'@squarePath', $newSquarePath);
                            }
                        }
                    }
                    $row['column_field_6'] = acmsSerialize($Field);
                    break;
                default:
                    break;
            }
            $newClid    = $DB->query(SQL::nextval('column_id', dsn()), 'seq');
            $map[intval($row['column_id'])] = $newClid;
            $row['column_id']       = $newClid;
            $row['column_entry_id'] = $newEid;

            $SQL    = SQL::newInsert('column_rev');
            foreach ( $row as $fd => $val ) {
                $SQL->addInsert($fd, $val);
            }
            if ( !$sourceRev ) {
                $SQL->addInsert('column_rev_id', 1);
            }
            $DB->query($SQL->get(dsn()), 'exec');
        } while ( $row = $DB->fetch($q) );}

        //-------
        // entry
        if ( $sourceRev ) {
            $SQL    = SQL::newSelect('entry_rev');
            $SQL->addWhereOpr('entry_rev_id', 1);
        } else {
            $SQL    = SQL::newSelect('entry');
        }
        $SQL->addWhereOpr('entry_id', $eid);
        $SQL->addWhereOpr('entry_blog_id', $bid);
        $row = $DB->query($SQL->get(dsn()), 'row');
        $title  = $row['entry_title'].config('entry_title_duplicate_suffix');
        $code   = ('on' == config('entry_code_title')) ? stripWhitespace($title) : config('entry_code_prefix').$newEid;
        if ( !!config('entry_code_extension') and !strpos($code, '.') ) $code .= ('.'.config('entry_code_extension'));

        $uid    = intval($row['entry_user_id']);
        if ( !($cid = intval($row['entry_category_id'])) ) $cid = null;;

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
        if ( config('update_datetime_as_duplicate_entry') !== 'off' ) {
            $row['entry_datetime'] = date('Y-m-d H:i:s', REQUEST_TIME);
        }
        $row['entry_posted_datetime']   = date('Y-m-d H:i:s', REQUEST_TIME);
        $row['entry_updated_datetime']  = date('Y-m-d H:i:s', REQUEST_TIME);
        $row['entry_hash']              = md5(SYSTEM_GENERATED_DATETIME.date('Y-m-d H:i:s', REQUEST_TIME));
        $row['entry_primary_image']     = !empty($map[$row['entry_primary_image']]) ? $map[$row['entry_primary_image']] : null;
        $row['entry_sort']              = $esort;
        $row['entry_user_sort']         = $usort;
        $row['entry_category_sort']     = $csort;
        $row['entry_user_id']           = SUID;
        $SQL    = SQL::newInsert('entry');
        foreach ( $row as $fd => $val ) {
            if ( !in_array($fd, array(
                'entry_approval',
                'entry_approval_public_point',
                'entry_approval_reject_point',
                'entry_last_update_user_id',
                'entry_rev_id',
                'entry_rev_status',
                'entry_rev_memo',
                'entry_rev_user_id',
                'entry_rev_datetime',
                'entry_current_rev_id'
            )) ) {
                $SQL->addInsert($fd, $val);
            }
        }
        $SQL->addInsert('entry_approval', 'pre_approval');
        $SQL->addInsert('entry_last_update_user_id', SUID);
        $DB->query($SQL->get(dsn()), 'exec');

        $SQL    = SQL::newInsert('entry_rev');
        foreach ( $row as $fd => $val ) {
            if ( !in_array($fd, array(
                'entry_current_rev_id',
                'entry_last_update_user_id',
                'entry_rev_id',
                'entry_rev_user_id',
                'entry_rev_datetime'
            )) ) {
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
        if ( $DB->query($q, 'fetch') and ($row = $DB->fetch($q)) ) { do {
            $row['tag_entry_id']    = $newEid;
            $Insert = SQL::newInsert('tag_rev');
            foreach ( $row as $fd => $val ) $Insert->addInsert($fd, $val);
            if ( !$sourceRev ) $Insert->addInsert('tag_rev_id', 1);
            $DB->query($Insert->get(dsn()), 'exec');
        } while ( $row = $DB->fetch($q) ); }

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
        if ( $sourceRev ) {
            $Field  = loadEntryField($eid, 1);
        } else {
            $Field  = loadEntryField($eid);
        }
        foreach ( $Field->listFields() as $fd ) {
            if ( !strpos($fd, '@path') ) {
                continue;
            }
            $set = false;
            $base   = substr($fd, 0, (-1 * strlen('@path')));
            foreach ( $Field->getArray($fd, true) as $i => $path ) {
                if ( !Storage::isFile($sourceDir.$path) ) continue;
                $info       = pathinfo($path);
                $dirname    = empty($info['dirname']) ? '' : $info['dirname'].'/';
                Storage::makeDirectory(REVISON_ARCHIVES_DIR.$dirname);
                $ext        = empty($info['extension']) ? '' : '.'.$info['extension'];
                $newPath    = $dirname.uniqueString().$ext;

                $path       = $sourceDir.$path;
                $largePath  = otherSizeImagePath($path, 'large');
                $tinyPath   = otherSizeImagePath($path, 'tiny');
                $squarePath = otherSizeImagePath($path, 'square');

                $newLargePath   = otherSizeImagePath($newPath, 'large');
                $newTinyPath    = otherSizeImagePath($newPath, 'tiny');
                $newSquarePath  = otherSizeImagePath($newPath, 'square');

                Storage::copy($path, REVISON_ARCHIVES_DIR.$newPath);
                Storage::copy($largePath, REVISON_ARCHIVES_DIR.$newLargePath);
                Storage::copy($tinyPath, REVISON_ARCHIVES_DIR.$newTinyPath);
                Storage::copy($squarePath, REVISON_ARCHIVES_DIR.$newSquarePath);

                if ( !$set ) {
                    $Field->delete($fd);
                    $Field->delete($base.'@largePath');
                    $Field->delete($base.'@tinyPath');
                    $Field->delete($base.'@squarePath');
                    $set = true;
                }
                $Field->add($fd, $newPath);
                if ( Storage::isReadable($largePath) ) {
                    $Field->add($base.'@largePath', $newLargePath);
                }
                if ( Storage::isReadable($tinyPath) ) {
                    $Field->add($base.'@tinyPath', $newTinyPath);
                }
                if ( Storage::isReadable($squarePath) ) {
                    $Field->add($base.'@squarePath', $newSquarePath);
                }
            }
        }
        Entry::saveFieldRevision($newEid, $Field, 1);
    }

    function dupe($eid, $newEid)
    {
        $DB     = DB::singleton(dsn());
        $bid    = ACMS_RAM::entryBlog($eid);

        //--------
        // column
        $map    = array();
        $SQL    = SQL::newSelect('column');
        $SQL->addWhereOpr('column_entry_id', $eid);
        $SQL->addWhereOpr('column_blog_id', $bid);
        $q  = $SQL->get(dsn());
        if ( $DB->query($q, 'fetch') and ($row = $DB->fetch($q)) ) { do {
            $type = detectUnitTypeSpecifier($row['column_type']);
            switch ( $type ) {
                case 'image':
                    $oldAry = explodeUnitData($row['column_field_2']);
                    $newAry = array();
                    foreach ( $oldAry as $old ) {
                        $info   = pathinfo($old);
                        $dirname= empty($info['dirname']) ? '' : $info['dirname'].'/';
                        $ext    = empty($info['extension']) ? '' : '.'.$info['extension'];
                        $newOld = $dirname.uniqueString().$ext;
                        $path   = ARCHIVES_DIR.$old;
                        $large  = otherSizeImagePath($path, 'large');
                        $tiny   = otherSizeImagePath($path, 'tiny');
                        $square = otherSizeImagePath($path, 'square');
                        $newPath    = ARCHIVES_DIR.$newOld;
                        $newLarge   = otherSizeImagePath($newPath, 'large');
                        $newTiny    = otherSizeImagePath($newPath, 'tiny');
                        $newSquare  = otherSizeImagePath($newPath, 'square');
                        copyFile($path, $newPath);
                        copyFile($large, $newLarge);
                        copyFile($tiny, $newTiny);
                        copyFile($square, $newSquare);
                        $newAry[]   = $newOld;
                    }
                    $row['column_field_2']  = implodeUnitData($newAry);
//                    $row['column_data']   = serialize($data);
                    break;
                case 'file':
                    $oldAry = explodeUnitData($row['column_field_2']);
                    $newAry = array();
                    foreach ( $oldAry as $old ) {
                        $info   = pathinfo($old);
                        $dirname= empty($info['dirname']) ? '' : $info['dirname'].'/';
                        $ext    = empty($info['extension']) ? '' : '.'.$info['extension'];
                        $newOld = $dirname.uniqueString().$ext;
                        $path   = ARCHIVES_DIR.$old;
                        $newPath    = ARCHIVES_DIR.$newOld;
                        copyFile($path, $newPath);

                        $newAry[]   = $newOld;
                    }
                    $row['column_field_2']  = implodeUnitData($newAry);
//                    $row['column_data']   = serialize($data);
                    break;
                case 'custom':
                    $Field = acmsUnserialize($row['column_field_6']);
                    $this->fieldDupe($Field);
                    $row['column_field_6'] = acmsSerialize($Field);
                default:
                    break;
            }
            $newClid    = $DB->query(SQL::nextval('column_id', dsn()), 'seq');
            $map[intval($row['column_id'])] = $newClid;
            $row['column_id']       = $newClid;
            $row['column_entry_id'] = $newEid;

            $SQL    = SQL::newInsert('column');
            foreach ( $row as $fd => $val ) {
                $SQL->addInsert($fd, $val);
            }
            $DB->query($SQL->get(dsn()), 'exec');
        } while ( $row = $DB->fetch($q) );}

        //-------
        // entry
        $SQL    = SQL::newSelect('entry');
        $SQL->addWhereOpr('entry_id', $eid);
        $SQL->addWhereOpr('entry_blog_id', $bid);
        $row = $DB->query($SQL->get(dsn()), 'row');
        $title  = $row['entry_title'].config('entry_title_duplicate_suffix');
        $code   = ('on' == config('entry_code_title')) ? stripWhitespace($title) : config('entry_code_prefix').$newEid;
        if ( !!config('entry_code_extension') and !strpos($code, '.') ) $code .= ('.'.config('entry_code_extension'));

        $uid    = intval($row['entry_user_id']);
        if ( !($cid = intval($row['entry_category_id'])) ) $cid = null;;

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
        if ( config('update_datetime_as_duplicate_entry') !== 'off' ) {
            $row['entry_datetime'] = date('Y-m-d H:i:s', REQUEST_TIME);
        }
        $row['entry_posted_datetime']   = date('Y-m-d H:i:s', REQUEST_TIME);
        $row['entry_updated_datetime']  = date('Y-m-d H:i:s', REQUEST_TIME);
        $row['entry_hash']              = md5(SYSTEM_GENERATED_DATETIME.date('Y-m-d H:i:s', REQUEST_TIME));
        $row['entry_primary_image']     = !empty($map[$row['entry_primary_image']]) ? $map[$row['entry_primary_image']] : null;
        $row['entry_sort']              = $esort;
        $row['entry_user_sort']         = $usort;
        $row['entry_category_sort']     = $csort;
        $row['entry_user_id']           = SUID;
        $SQL    = SQL::newInsert('entry');
        foreach ( $row as $fd => $val ) {
            if ( $fd == 'entry_current_rev_id' ) {
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
        if ( $DB->query($q, 'fetch') and ($row = $DB->fetch($q)) ) { do {
            $row['tag_entry_id']    = $newEid;
            $Insert = SQL::newInsert('tag');
            foreach ( $row as $fd => $val ) $Insert->addInsert($fd, $val);
            $DB->query($Insert->get(dsn()), 'exec');
        } while ( $row = $DB->fetch($q) ); }

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
}
