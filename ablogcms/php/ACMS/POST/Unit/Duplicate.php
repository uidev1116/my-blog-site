<?php

class ACMS_POST_Unit_Duplicate extends ACMS_POST_Unit
{
    function post()
    {
        $utid   = UTID;
        $eid    = EID;
        $entry  = ACMS_RAM::entry($eid);

        if ( !$eid ) die();
        if ( !IS_LICENSED ) die();
        if ( !roleEntryAuthorization(BID, $entry) ) die();

        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('column');
        $SQL->addWhereOpr('column_id', UTID);
        $SQL->addWhereOpr('column_entry_id', EID);
        $SQL->addWhereOpr('column_blog_id', BID);
        $unit   = $DB->query($SQL->get(dsn()), 'row');

        $sort   = intval($unit['column_sort']);
        $nclid  = $DB->query(SQL::nextval('column_id', dsn()), 'seq');

        //----------
        // fix sort
        $SQL    = SQL::newUpdate('column');
        $SQL->addUpdate('column_sort', SQL::newField('column_sort + 1'));
        $SQL->addWhereOpr('column_sort', $sort, '>');
        $SQL->addWhereOpr('column_entry_id', EID);
        $SQL->addWhereOpr('column_blog_id', BID);
        $DB->query($SQL->get(dsn()), 'exec');

        //---------
        // insert
        if ( detectUnitTypeSpecifier($unit['column_type']) == 'image' ) {
            $oldAry = explodeUnitData($unit['column_field_2']);
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
            $unit['column_field_2']  = implodeUnitData($newAry);
        } else if ( detectUnitTypeSpecifier($unit['column_type']) == 'file' ) {
            $old    = $unit['column_field_2'];
            $oldAry = explodeUnitData($unit['column_field_2']);
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
            $unit['column_field_2']  = implodeUnitData($newAry);
        } else if (detectUnitTypeSpecifier($unit['column_type']) == 'custom') {
            $Field = acmsUnserialize($unit['column_field_6']);
            foreach ( $Field->listFields() as $fd ) {
                if ( !strpos($fd, '@path') ) {
                    continue;
                }
                $base = substr($fd, 0, (-1 * strlen('@path')));
                $set = false;
                foreach ( $Field->getArray($fd, true) as $i => $path ) {
                    if ( !Storage::isFile(ARCHIVES_DIR . $path) ) continue;
                    $info       = pathinfo($path);
                    $dirname    = empty($info['dirname']) ? '' : $info['dirname'].'/';
                    Storage::makeDirectory(ARCHIVES_DIR . $dirname);
                    $ext        = empty($info['extension']) ? '' : '.'.$info['extension'];
                    $newPath    = $dirname . uniqueString() . $ext;

                    $path       = ARCHIVES_DIR . $path;
                    $largePath  = otherSizeImagePath($path, 'large');
                    $tinyPath   = otherSizeImagePath($path, 'tiny');
                    $squarePath = otherSizeImagePath($path, 'square');

                    $newLargePath   = otherSizeImagePath($newPath, 'large');
                    $newTinyPath    = otherSizeImagePath($newPath, 'tiny');
                    $newSquarePath  = otherSizeImagePath($newPath, 'square');

                    Storage::copy($path, ARCHIVES_DIR . $newPath);
                    Storage::copy($largePath, ARCHIVES_DIR . $newLargePath);
                    Storage::copy($tinyPath, ARCHIVES_DIR . $newTinyPath);
                    Storage::copy($squarePath, ARCHIVES_DIR . $newSquarePath);


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
            $unit['column_field_6'] = acmsSerialize($Field);
        }

        $SQL    = SQL::newInsert('column');
        $SQL->addInsert('column_id', $nclid);
        $SQL->addInsert('column_sort', $sort + 1);
        foreach ( $unit as $key => $val ) {
            if ( in_array($key, array('column_id', 'column_sort', 'column_group')) ) {
                continue;
            }
            $SQL->addInsert($key, $val);
        }
        $DB->query($SQL->get(dsn()), 'exec');

        //----------
        // fulltext
        Common::saveFulltext('eid', EID, Common::loadEntryFulltext(EID));

        $this->fixEntry(EID);

        $this->redirect(acmsLink(array(
            'tpl'   => 'include/unit-fetch.html',
            'utid'  => $nclid,
            'eid'   => EID,
        )));

        return true;
    }
}
