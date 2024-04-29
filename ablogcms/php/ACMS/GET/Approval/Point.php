<?php

class ACMS_GET_Approval_Point extends ACMS_GET
{
    public function get()
    {
        if (!enableApproval()) {
            return '';
        }
        if (!editionIsEnterprise()) {
            return '';
        }
        if (!RVID) {
            return '';
        }

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $DB     = DB::singleton(dsn());
        $vars   = [];
        $workflow = loadWorkflow(BID, CID);
        if ($workflow->isNull()) {
            return '';
        }

        $type = $workflow->get('workflow_type');
        $vars['approval_public_pass_point'] = $workflow->get('workflow_public_point');
        $vars['approval_reject_pass_point'] = $workflow->get('workflow_reject_point');
        $vars['approval_user_point'] = approvalUserPoint(BID, CID);

        if ($type !== 'parallel') {
            return '';
        }

        $SQL = SQL::newSelect('entry_rev');
        $SQL->addWhereOpr('entry_id', EID);
        $SQL->addWhereOpr('entry_rev_id', RVID);
        $SQL->addWhereOpr('entry_blog_id', BID);
        if ($entry = $DB->query($SQL->get(dsn()), 'row')) {
            foreach ($entry as $key => $val) {
                $key = substr($key, strlen('entry_'));
                $vars[$key] = $val;
            }
        }
        $Tpl->add(null, $vars);

        return $Tpl->get();
    }
}
