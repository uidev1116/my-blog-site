<?php

class ACMS_POST_Config extends ACMS_POST
{
    /**
     * CSRF対策
     *
     * @var bool
     */
    protected $isCSRF = true;

    /**
     * 二重送信対策
     *
     * @var bool
     */
    protected $checkDoubleSubmit = true;

    /**
     * @inheritDoc
     */
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

        $Config = $this->extract('config');
        $Config = Config::setValide($Config, $rid, $mid, $setid);
        $Config->validate(new ACMS_Validator());
        $Config = Config::fix($Config);

        if ($this->Post->isValidAll()) {
            $this->saveConfig($Config, BID, $rid, $mid, $setid);
            $this->Post->set('notice_mess', 'show');
            $this->Post->set('edit', 'update');

            AcmsLogger::info('「' . ADMIN . '」のコンフィグを保存しました', [
                'bid' => BID,
                'rid' => $rid,
                'setid' => $setid,
                'mid' => $mid,
                'data' => $Config->_aryField,
            ]);
        } else {
            AcmsLogger::info('「' . ADMIN . '」のコンフィグ保存に失敗しました', [
                'bid' => BID,
                'rid' => $rid,
                'setid' => $setid,
                'mid' => $mid,
                'validator' => $Config->_aryV,
            ]);
        }

        return $this->Post;
    }

    /**
     * ToDo: deprecated method 2.7.0
     * @deprecated
     */
    protected function resetConfig(&$Config, $bid = BID, $rid = null, $mid = null, $setid = null)
    {
        Config::resetConfig($Config, $bid, $rid, $mid, $setid);
    }

    /**
     * ToDo: deprecated method 2.7.0
     * @deprecated
     */
    protected function saveConfig(&$Config, $bid = BID, $rid = null, $mid = null, $setid = null)
    {
        return Config::saveConfig($Config, $bid, $rid, $mid, $setid);
    }

    /**
     * ToDo: deprecated method 2.7.0
     * @deprecated
     */
    protected function fix($Config)
    {
        return Config::fix($Config);
    }

    /**
     * ToDo: deprecated method 2.7.0
     * @deprecated
     */
    protected function setValide(&$Config, $rid = null, $mid = null, $setid = null)
    {
        $Config = Config::setValide($Config, $rid, $mid, $setid);
    }
}
