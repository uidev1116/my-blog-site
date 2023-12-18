<?php

class ACMS_POST_Entry_Add extends ACMS_POST_Entry
{
    function post()
    {
        if ( !EID ) die();
        if ( !IS_LICENSED ) die();
        if ( !sessionWithCompilation() ) {
            if ( !sessionWithContribution() ) die();
            if ( SUID <> ACMS_RAM::entryUser(EID) ) die();
        }

        if ( !$Column = Entry::extractColumn() ) {
            return acmsLink(array(
                'bid'   => BID,
                'eid'   => EID,
            ));
        }
        $DB  = DB::singleton(dsn());
        $Res = Entry::saveColumn($Column, EID, BID, true);
        $Column = Entry::getSavedColumn();

        // エントリーにメイン画像がなく，今回画像ユニットが追加されていたら，先頭の1つをメイン画像にする．
        if ( !($utid = ACMS_RAM::entryPrimaryImage(EID)) && !!($utid = reset($Res)) ) {
            $SQL    = SQL::newUpdate('entry');
            $SQL->addUpdate('entry_primary_image', $utid);
            $SQL->addWhereOpr('entry_id', EID);
            $DB->query($SQL->get(dsn()), 'exec');
            ACMS_RAM::entry(EID, null);

        }

        //----------
        // fulltext
        Common::saveFulltext('eid', EID, Common::loadEntryFulltext(EID));

        $SQL = SQL::newUpdate('entry');
        $SQL->addUpdate('entry_current_rev_id', 0);
        $SQL->addUpdate('entry_reserve_rev_id', 0);
        $SQL->addUpdate('entry_last_update_user_id', SUID);
        $SQL->addWhereOpr('entry_id', EID);
        $SQL->addWhereOpr('entry_blog_id', BID);
        $DB->query($SQL->get(dsn()), 'exec');
        ACMS_RAM::entry(EID, null);
        $this->clearCache(BID, EID);

        AcmsLogger::info('「' . ACMS_RAM::entryTitle(EID) . '」エントリーにユニットを追加しました');

        $this->redirect(acmsLink(array(
            'bid'   => BID,
            'eid'   => EID,
        )));
    }
}
