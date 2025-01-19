<?php

use Acms\Services\Facades\Application;
use Acms\Services\Facades\Common;
use Acms\Services\Facades\Logger;
use Acms\Services\Facades\Database;

class ACMS_POST_Entry_Add extends ACMS_POST_Entry
{
    function post()
    {
        if (!EID) {
            die();
        }
        if (!IS_LICENSED) {
            die();
        }
        if (!sessionWithCompilation()) {
            if (!sessionWithContribution()) {
                die();
            }
            if (SUID <> ACMS_RAM::entryUser(EID)) {
                die();
            }
        }

        $unitRepository = Application::make('unit-repository');
        assert($unitRepository instanceof \Acms\Services\Unit\Repository);

        $units = $unitRepository->extractUnits(null);
        if (empty($units)) {
            return acmsLink([
                'bid'   => BID,
                'eid'   => EID,
            ]);
        }
        $imageUnitIdTable = $unitRepository->saveUnits($units, EID, BID, true);

        // エントリーにメイン画像がなく，今回画像ユニットが追加されていたら，先頭の1つをメイン画像にする．
        if (!($utid = ACMS_RAM::entryPrimaryImage(EID)) && !!($utid = reset($imageUnitIdTable))) {
            $SQL = SQL::newUpdate('entry');
            $SQL->addUpdate('entry_primary_image', $utid);
            $SQL->addWhereOpr('entry_id', EID);
            Database::query($SQL->get(dsn()), 'exec');
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
        Database::query($SQL->get(dsn()), 'exec');
        ACMS_RAM::entry(EID, null);
        $this->clearCache(BID, EID);

        Logger::info('「' . ACMS_RAM::entryTitle(EID) . '」エントリーにユニットを追加しました');

        $this->redirect(acmsLink([
            'bid'   => BID,
            'eid'   => EID,
        ]));
    }
}
