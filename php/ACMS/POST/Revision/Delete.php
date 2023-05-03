<?php

class ACMS_POST_Revision_Delete extends ACMS_POST
{
    function post()
    {
        if ( !EID ) die();
        if ( !RVID ) die();
        if ( !IS_LICENSED ) die();

        if ( roleAvailableUser() ) {
            if ( !roleAuthorization('entry_edit', BID, EID) ) die();
        } else {
            if ( !sessionWithCompilation(BID, false) ) {
                if ( !sessionWithContribution(BID, false) ) die();
                if ( SUID <> ACMS_RAM::entryUser(EID) ) die();
            }
        }
        $DB     = DB::singleton(dsn());

        //-------
        // entry
        $SQL    = SQL::newDelete('entry_rev');
        $SQL->addWhereOpr('entry_id', EID);
        $SQL->addWhereOpr('entry_rev_id', RVID);
        $SQL->addWhereOpr('entry_blog_id', BID);
        $DB->query($SQL->get(dsn()), 'exec');

        //--------------
        // image, file
        $SQL    = SQL::newSelect('column_rev');
        $SQL->addWhereOpr('column_entry_id', EID);
        $SQL->addWhereOpr('column_rev_id', RVID);
        $SQL->addWhereOpr('column_blog_id', BID);
        $q      = $SQL->get(dsn());
        if ( $DB->query($q, 'fetch') and ($row = $DB->fetch($q)) ) { do {
            switch ( $row['column_type'] ) {
                case 'image':
                    if ( empty($row['column_field_2']) ) break;
                    $oldAry = explodeUnitData($row['column_field_2']);
                    foreach ( $oldAry as $old ) {
                        $path   = REVISON_ARCHIVES_DIR.$old;
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
                    if ( empty($row['column_field_2']) ) break;
                    $oldAry = explodeUnitData($row['column_field_2']);
                    foreach ( $oldAry as $old ) {
                        $path   = REVISON_ARCHIVES_DIR.$old;
                        deleteFile($path);
                    }
                    break;
            }
        } while ( $row = $DB->fetch($q) ); }

        //-------
        // unit
        $SQL    = SQL::newDelete('column_rev');
        $SQL->addWhereOpr('column_entry_id', EID);
        $SQL->addWhereOpr('column_rev_id', RVID);
        $SQL->addWhereOpr('column_blog_id', BID);
        $DB->query($SQL->get(dsn()), 'exec');

        //-------
        // field
        $Field  = loadEntryField(EID, RVID);
        foreach ( $Field->listFields() as $fd ) {
            if ( 1
                and !strpos($fd, '@path')
                and !strpos($fd, '@tinyPath')
                and !strpos($fd, '@largePath')
                and !strpos($fd, '@squarePath')
            ) {
                continue;
            }
            foreach ( $Field->getArray($fd, true) as $i => $path ) {
                if ( !Storage::isFile(REVISON_ARCHIVES_DIR.$path) ) continue;
                Storage::remove(REVISON_ARCHIVES_DIR.$path);
            }
        }
        Common::saveField('eid', EID, null, null, RVID);

        //-------
        // tag
        $SQL    = SQL::newDelete('tag_rev');
        $SQL->addWhereOpr('tag_entry_id', EID);
        $SQL->addWhereOpr('tag_rev_id', RVID);
        $SQL->addWhereOpr('tag_blog_id', BID);
        $DB->query($SQL->get(dsn()), 'exec');

        //-----------------
        // relation entry
        $SQL    = SQL::newDelete('relationship_rev');
        $SQL->addWhereOpr('relation_id', EID);
        $SQL->addWhereOpr('relation_rev_id', RVID);
        $DB->query($SQL->get(dsn()), 'exec');

        die('OK');
    }
}
