<?php

class ACMS_POST_Unit_Remove extends ACMS_POST_Unit
{
    function post()
    {
        $eid    = EID;
        $entry  = ACMS_RAM::entry($eid);
        if (!roleEntryUpdateAuthorization(BID, $entry)) die();

        $DB     = DB::singleton(dsn());

        $SQL    = SQL::newSelect('column');
        $SQL->addWhereOpr('column_id', UTID);
        $SQL->addWhereOpr('column_entry_id', $eid);
        $q      = $SQL->get(dsn());

        $targetUnits = [];

        if ( $DB->query($q, 'fetch') and ($row = $DB->fetch($q)) ) { do {
            $targetUnits[] = $row;

            switch ( $row['column_type'] ) {
                case 'image':
                    if ( empty($row['column_field_2']) ) break;
                    $oldAry = explodeUnitData($row['column_field_2']);
                    foreach ( $oldAry as $old ) {
                        $path   = ARCHIVES_DIR.$row['column_field_2'];
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
                        $path   = ARCHIVES_DIR.$old;
                        deleteFile($path);
                    }
                    break;
            }
        } while ( $row = $DB->fetch($q) ); }

        $SQL    = SQL::newDelete('column');
        $SQL->addWhereOpr('column_id', UTID);
        $SQL->addWhereOpr('column_entry_id', $eid);
        $DB->query($SQL->get(dsn()), 'exec');

        $this->fixEntry($eid);

        AcmsLogger::info('「' . ACMS_RAM::entryTitle(EID) . '」エントリーの指定ユニットを削除しました', $targetUnits);

        return $this->Post;
    }
}
