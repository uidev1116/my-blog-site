<?php

class ACMS_GET_Admin_Entry_Revision_Info extends ACMS_GET_Admin_Entry_Revision
{
    public function get()
    {
        if (!sessionWithContribution(BID, false)) {
            return 'Bad Access.';
        }
        if (!EID) {
            return '';
        }
        if (!RVID) {
            return '';
        }
        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        $revision = $this->getRevision(EID, RVID);
        $isReserve = strtotime($revision['entry_start_datetime']) > REQUEST_TIME;
        $vars = [];

        $sql = SQL::newSelect('entry');
        $sql->setSelect('entry_current_rev_id');
        $sql->addWhereOpr('entry_id', EID);
        $currentRevId = DB::query($sql->get(dsn()), 'one');

        if (RVID === 1) {
        } else if (RVID === intval($currentRevId)) {
        } else if (roleAvailableUser()) {
            if (0
                || (enableApproval(BID, $revision['entry_category_id']) && sessionWithApprovalPublic(BID, $revision['entry_category_id']))
                || (!enableApproval(BID, $revision['entry_category_id']) && roleAuthorization('entry_edit', BID, EID))
            ) {
                $Tpl->add('revisionChange', [
                    'canChange' => '1',
                    'isReserve' => $isReserve ? '1' : '0',
                    'reserveDatetime' => $isReserve ? $revision['entry_start_datetime'] : '',
                ]);
            }
        } else if (enableApproval(BID, $revision['entry_category_id'])) {
            if (sessionWithApprovalAdministrator(BID, $revision['entry_category_id']) && isset($revision['entry_rev_status']) && $revision['entry_rev_status'] === 'approved') {
                $Tpl->add('revisionChange', [
                    'canChange' => '1',
                    'isReserve' => $isReserve ? '1' : '0',
                    'reserveDatetime' => $isReserve ? $revision['entry_start_datetime'] : '',
                ]);
            }
        } else {
            do {
                if (!sessionWithCompilation(BID, false)) {
                    if (!sessionWithContribution(BID, false)) {
                        break;
                    }
                    if (SUID != ACMS_RAM::entryUser(EID)) {
                        break;
                    }
                }
                $Tpl->add('revisionChange', [
                    'canChange' => '1',
                    'isReserve' => $isReserve ? '1' : '0',
                    'reserveDatetime' => $isReserve ? $revision['entry_start_datetime'] : '',
                ]);
            } while (false);
        }
        if (sessionWithApprovalAdministrator(BID, $revision['entry_category_id']) || intval($revision['entry_rev_user_id']) === SUID) {
            $Tpl->add('edit');
        }
        if ($revision) {
            $auid = $revision['entry_rev_user_id'];
            $author = ACMS_RAM::user($auid);

            $status = '承認前';
            switch ($revision['entry_rev_status']) {
                case 'in_review':
                    $status = '承認中';
                    break;
                case 'reject':
                    $status = '承認却下';
                    break;
                case 'approved':
                    $status = '承認済み';
                    break;
                default:
                    $status = '承認前';
                    break;
            }
            if ($revision['entry_status'] === 'trash') {
                $status .= ' 削除依頼';
            }

            $vars = array(
                'rvid' => RVID,
                'memo' => $revision['entry_rev_memo'],
                'author' => $author['user_name'],
                'icon' => loadUserIcon($auid),
                'status_code' => $revision['entry_rev_status'],
                'isReserve' => $isReserve ? '1' : '0',
                'reserveDatetime' => $isReserve ? $revision['entry_start_datetime'] : '',
                'datetime' => $revision['entry_rev_datetime'],
                'url' => acmsLink(array(
                    'eid' => EID,
                    'bid' => BID,
                    'aid' => $this->Get->get('aid', null),
                    'query' => array(
                        'rvid' => RVID,
                        'trash' => 'show',
                    ),
                )),
            );
            if (enableApproval(BID, CID)) {
                $vars['status'] = $status;
            }
        }
        $Tpl->add(null, $vars);

        return $Tpl->get();
    }
}
