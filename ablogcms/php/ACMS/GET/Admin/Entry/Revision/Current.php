<?php

class ACMS_GET_Admin_Entry_Revision_Current extends ACMS_GET_Admin_Entry_Revision
{
    public function get()
    {
        if (!sessionWithContribution(BID, false)) {
            return 'Bad Access.';
        }
        if (!defined('EID')) {
            return '';
        }
        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        $vars = [];

        $currentRvid = $this->getCurrentRevisionId(EID);
        $currentVersion = $this->getRevision(EID, RVID);
        $count = $this->countRevisions(EID);

        if ($currentRvid > 0) {
            $vars['currentVersion'] = $currentRvid;
            if (isset($currentVersion['entry_rev_memo'])) {
                $vars['currentVersionName'] = $currentVersion['entry_rev_memo'];
            }
        } else {
            $Tpl->add('notExistCurrentVersion');
        }
        if (isset($currentVersion['entry_rev_status'])) {
            $vars['rev_status'] = $currentVersion['entry_rev_status'];
        }
        $vars['confirmUrl'] = acmsLink([
            'bid' => BID,
            'eid' => EID,
            'cid' => CID,
            'aid' => $this->Get->get('aid'),
            'query' => array(
                'rvid' => RVID,
                'aid' => $this->Get->get('aid'),
            ),
        ]);
        if ($count > 0) {
            $vars['amount'] = $count;
        } else {
            $Tpl->add('notFound');
        }
        $Tpl->add(null, $vars);

        return $Tpl->get();
    }
}
