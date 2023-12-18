<?php

class ACMS_POST_Entry_Open extends ACMS_POST_Entry
{
    function post()
    {
        $DB     = DB::singleton(dsn());
        $this->Post->reset(true);
        $this->Post->setMethod('entry', 'operable', (1
            and !!($eid = intval($this->Post->get('eid')))
            and !!IS_LICENSED
            and ( 0
                or sessionWithCompilation()
                or ( 1
                    and sessionWithContribution()
                    and SUID == ACMS_RAM::entryUser($eid)
                )
            )
//            and ( 0
//                or !($cid = ACMS_RAM::entryCategory($eid))
//                or 'open' == ACMS_RAM::categoryStatus($cid)
//            )
        ));
        $this->Post->validate();

        if ( $this->Post->isValidAll() ) {
            $SQL    = SQL::newUpdate('entry');
            $SQL->addUpdate('entry_status', 'open');
            if ( 'draft' == ACMS_RAM::entryStatus($eid) && config('update_datetime_as_entry_open') !== 'off' ) {
                $SQL->addUpdate('entry_datetime', date('Y-m-d H:i:s', REQUEST_TIME));
            }
            $SQL->addWhereOpr('entry_id', $eid);
            $DB->query($SQL->get(dsn()), 'exec');

            ACMS_RAM::entry($eid, null);

            AcmsLogger::info('「' . ACMS_RAM::entryTitle($eid) . '」エントリーを公開しました');

            //-------------------
            // キャッシュクリア予約
            Entry::updateCacheControl(ACMS_RAM::entryStartDatetime($eid), ACMS_RAM::entryEndDatetime($eid), ACMS_RAM::entryBlog($eid), $eid);

            $this->redirect(acmsLink(array(
                'bid'   => BID,
                'eid'   => $eid,
            )));
        }

        //------
        // Hook
        if (HOOK_ENABLE) {
            $Hook = ACMS_Hook::singleton();
            $Hook->call('saveEntry', array($eid, 1));
            Webhook::call(BID, 'entry', 'entry:opened', array($eid, null));
        }

        return $this->Post;
    }
}
