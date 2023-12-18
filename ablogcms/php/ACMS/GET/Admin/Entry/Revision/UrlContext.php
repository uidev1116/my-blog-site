<?php

class ACMS_GET_Admin_Entry_Revision_UrlContext extends ACMS_GET_Admin_Entry_Revision
{
    public function get()
    {
        if (!sessionWithContribution(BID, false)) {
            return 'Bad Access.';
        }
        if (!defined('EID')) {
            return '';
        }
        if (!defined('RVID')) {
            return '';
        }
        $tpl = new Template($this->tpl, new ACMS_Corrector());
        $revision = $this->getRevision(EID, RVID);

        /**
         * そのまま公開保存できるか判定
         */
        do {
            // RVIDが1以上の場合、バージョン編集なので「そのまま保存できない」
            if (RVID > 1) {
                break;
            }
            // 承認機能有効で、管理者でない場合、保存できない
            if (enableApproval(BID, CID) && !sessionWithApprovalAdministrator(BID, CID)) {
                break;
            }
            $tpl->add('enbaleUpdateEntry');
        } while (false);

        /**
         * バージョンを更新できるか判定
         */
        do {
            // 更新するバージョンがない or 作業領域の場合、バージョン保存はできない
            if (empty(RVID) || RVID === 1) {
                break;
            }
            // 現在公開中のバージョンの場合は更新できない
            $currentEntry = ACMS_RAM::entry(EID);
            if (intval($currentEntry['entry_current_rev_id']) === RVID) {
                break;
            }
            // 承認機能有効の場合
            if (enableApproval(BID, CID)) {
                if (!sessionWithApprovalAdministrator(BID, CID)) {
                    // 最終承認できないユーザーで、承認前、承認中以外の場合は保存できない
                    if (!in_array($revision['entry_rev_status'], ['none', 'in_review'])) {
                        break;
                    }
                }
            }
            $tpl->add('enbaleUpdateVersion');
        } while (false);

        $vars = [
            'eid' => EID,
            'rvid' => RVID,
        ];
        if (isset($revision['entry_rev_memo'])) {
            $vars['memo'] = RVID === 1 ? '' : $revision['entry_rev_memo'];
        }
        $tpl->add(null, $vars);

        return $tpl->get();
    }
}
