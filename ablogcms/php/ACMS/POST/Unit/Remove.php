<?php

use Acms\Services\Facades\Application;
use Acms\Services\Facades\Logger as AcmsLogger;
use Acms\Services\Facades\Common;

class ACMS_POST_Unit_Remove extends ACMS_POST_Unit
{
    function post()
    {
        $eid = EID;
        $entry  = ACMS_RAM::entry($eid);
        if (!roleEntryUpdateAuthorization(BID, $entry)) {
            die();
        }
        try {
            // ユニットを削除
            /** @var \Acms\Services\Unit\Repository $unitRepository */
            $unitRepository = Application::make('unit-repository');
            $removedUnit = $unitRepository->removeUnit(UTID, $eid); // @phpstan-ignore-line
            // エントリ情報を更新
            $this->fixEntry($eid);
            // ログ
            AcmsLogger::info('「' . ACMS_RAM::entryTitle(EID) . '」エントリーの指定ユニットを削除しました', $removedUnit->getLegacyData()); // @phpstan-ignore-line
        } catch (Exception $e) {
            AcmsLogger::error('「' . ACMS_RAM::entryTitle(EID) . '」エントリーの指定ユニットの削除に失敗しました', Common::exceptionArray($e)); // @phpstan-ignore-line
        }

        return $this->Post;
    }
}
