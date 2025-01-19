<?php

class ACMS_POST_Revision_Change extends ACMS_POST_Entry
{
    function post()
    {
        try {
            if (!EID) {
                throw new \RuntimeException('エントリーが指定されていません');
            }
            if (enableApproval(BID, CID)) {
                if (!sessionWithApprovalAdministrator(BID, CID)) {
                    throw new \RuntimeException('権限がありません');
                }
            } elseif (roleAvailableUser()) {
                if (!roleAuthorization('entry_edit', BID, EID)) {
                    throw new \RuntimeException('権限がありません');
                }
            } else {
                if (!sessionWithCompilation(BID)) {
                    if (!sessionWithContribution(BID)) {
                        throw new \RuntimeException('権限がありません');
                    }
                    if (SUID <> ACMS_RAM::entryUser(EID)) {
                        throw new \RuntimeException('権限がありません');
                    }
                }
            }

            $rvid = $this->Post->get('revision');
            if (!is_numeric($rvid)) {
                throw new \RuntimeException('バージョン番号が指定されていません');
            }
            $cid = Entry::changeRevision($rvid, EID, BID);
            $revision = Entry::getRevision(EID, $rvid);

            AcmsLogger::info('「' . ACMS_RAM::entryTitle(EID) . '（' . $revision['entry_rev_memo'] . '）」を公開バージョンに切り替えました', [
                'eid' => EID,
                'rvid' => $rvid,
            ]);

            $this->redirect(acmsLink([
                'bid'   => BID,
                'eid'   => EID,
                'cid'   => $cid,
            ]));
        } catch (\Exception $e) {
            AcmsLogger::info('公開バージョンへの切り替えができませんでした。' . $e->getMessage(), Common::exceptionArray($e));
            return $this->Post;
        }
    }
}
