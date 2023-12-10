<?php

class ACMS_POST_Entry_Close extends ACMS_POST_Entry
{
    function post()
    {
        if ( !$eid = idval($this->Post->get('eid')) ) die();
        if ( !IS_LICENSED ) die();
        if ( !sessionWithCompilation() ) {
            if ( !sessionWithContribution() ) die();
            if ( SUID <> ACMS_RAM::entryUser($eid) ) die();
        }

        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newUpdate('entry');
        $SQL->setUpdate('entry_status', 'close');
        $SQL->addWhereOpr('entry_id', $eid);
        $DB->query($SQL->get(dsn()), 'exec');
        ACMS_RAM::entry($eid, null);
        $this->clearCache(BID, EID);

        //-----------------------
        // キャッシュクリア予約削除
        Entry::deleteCacheControl($eid);

        AcmsLogger::info('「' . ACMS_RAM::entryTitle(EID) . '」エントリーを非公開にしました');

        $this->redirect(acmsLink(array(
            'bid'   => BID,
            'eid'   => $eid,
        )));
    }
}
