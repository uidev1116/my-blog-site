<?php

use Acms\Services\Facades\Application;
use Acms\Services\Facades\Common;
use Acms\Services\Facades\Logger as AcmsLogger;

class ACMS_POST_Unit_Duplicate extends ACMS_POST_Unit
{
    function post()
    {
        $utid = UTID;
        $eid = EID;
        $entry = ACMS_RAM::entry($eid);

        if (!$eid) {
            die();
        }
        if (!IS_LICENSED) {
            die();
        }
        if (!roleEntryAuthorization(BID, $entry)) {
            die();
        }
        try {
            // ユニットをコピー
            $unitRepository = Application::make('unit-repository');
            assert($unitRepository instanceof \Acms\Services\Unit\Repository);
            $copiedUnit = $unitRepository->duplicateUnit($utid, $eid);
            // フルテキストを再生成
            Common::saveFulltext('eid', EID, Common::loadEntryFulltext(EID));
            // エントリー情報を更新
            $this->fixEntry(EID);
            // ログ
            AcmsLogger::info('「' . ACMS_RAM::entryTitle(EID) . '」エントリーの指定ユニットを複製しました', $copiedUnit->getLegacyData());
            // リダイレクト
            $this->redirect(acmsLink([
                'tpl'   => 'include/unit-fetch.html',
                'utid'  => $copiedUnit->getId(),
                'eid'   => EID,
            ]));
        } catch (Exception $e) {
            AcmsLogger::error('「' . ACMS_RAM::entryTitle(EID) . '」エントリーの指定ユニットの複製に失敗しました', Common::exceptionArray($e));
        }
        return $this->Post;
    }
}
