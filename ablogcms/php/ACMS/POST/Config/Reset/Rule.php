<?php

class ACMS_POST_Config_Reset_Rule extends ACMS_POST_Config
{
    public function post()
    {
        if (!$rid = idval($this->Post->get('rid'))) {
            $rid = null;
        }
        if (!$mid = idval($this->Post->get('mid'))) {
            $mid = null;
        }
        if (!$setid = idval($this->Post->get('setid'))) {
            $setid = null;
        }

        if (empty($rid)) {
            return $this->Post;
        }

        $Config = $this->extract('config');
        $Config = Config::setValide($Config, $rid, $mid, $setid);

        $Config->validate(new ACMS_Validator());
        $Config = Config::fix($Config);

        if ($this->Post->isValidAll()) {
            Config::resetConfig($Config, BID, $rid, $mid, $setid);
            $Config = null;
            $this->Post->set('notice_mess', 'show');
            $this->Post->set('edit', 'update');

            AcmsLogger::info('「' . ACMS_RAM::ruleName($rid) . '」ルールのコンフィグをリセットしました', [
                'rid' => $rid,
                'mid' => $mid,
                'setid' => $setid,
            ]);
        }

        return $this->Post;
    }
}
