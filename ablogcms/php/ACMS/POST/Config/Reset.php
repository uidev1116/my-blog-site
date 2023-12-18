<?php

class ACMS_POST_Config_Reset extends ACMS_POST
{
    function post()
    {
        if (!$rid = idval($this->Post->get('rid'))) {
            $rid = null;
        }
        if (!$setid = idval($this->Post->get('setid'))) {
            $setid = null;
        }
        $Config = $this->extract('config');
        $Config = Config::setValide($Config, $rid, null, $setid);
        $Config->validate(new ACMS_Validator());
        $Config = Config::fix($Config);

        if ($this->Post->isValidAll()) {
            Config::resetConfig($Config, BID, $rid, null, $setid);
            AcmsLogger::info('コンフィグのリセットを行いました', [
                'rid' => $rid,
                'setid' => $setid,
            ]);
            redirect(REQUEST_URL);
        }
        return $this->Post;
    }
}
