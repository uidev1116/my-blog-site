<?php

class ACMS_GET_Approval_RevisionInfo extends ACMS_GET
{
    public function get()
    {
        if (!enableApproval()) {
            return '';
        }
        if (!sessionWithApprovalPublic(BID, CID)) {
            return '';
        }
        if (!RVID || !EID) {
            return '';
        }

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $DB     = DB::singleton(dsn());
        $vars   = [];

        $SQL    = SQL::newSelect('entry_rev');
        $SQL->addWhereOpr('entry_id', EID);
        $SQL->addWhereOpr('entry_rev_id', RVID);
        $SQL->addWhereOpr('entry_blog_id', BID);
        if (!($entry = $DB->query($SQL->get(dsn()), 'row'))) {
            return '';
        }
        foreach ($entry as $key => $val) {
            $key = substr($key, strlen('entry_'));
            if ($key == 'start_datetime') {
                list($vars['start_date'], $vars['start_time'])  = explode(' ', $val);
            } elseif ($key == 'end_datetime') {
                list($vars['end_date'], $vars['end_time'])  = explode(' ', $val);
            } else {
                $vars[$key] = $val;
            }
        }
        $Tpl->add(null, $vars);

        return $Tpl->get();
    }
}
