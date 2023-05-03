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
     * @return false|Field
     */
    function post()
    {
        if ( !$rid = idval($this->Post->get('rid')) ) $rid = null;
        if ( !$mid = idval($this->Post->get('mid')) ) $mid = null;
        if ( !$setid = idval($this->Post->get('setid'))) $setid = null;

        $Config = $this->extract('config');
        $Config = Config::setValide($Config, $rid, $mid, $setid);
        $Config->validate(new ACMS_Validator());
        $Config = Config::fix($Config);

        if ( $this->Post->isValidAll() ) {
            $this->saveConfig($Config, BID, $rid, $mid, $setid);
            $this->Post->set('notice_mess', 'show');
            $this->Post->set('edit', 'update');
        }

        return $this->Post;
    }

    /**
     * ToDo: deprecated method 2.7.0
     */
    function resetConfig(& $Config, $bid=BID, $rid=null, $mid=null, $setid=null)
    {
        Config::resetConfig($Config, $bid, $rid, $mid, $setid);
    }

    /**
     * ToDo: deprecated method 2.7.0
     */
    function saveConfig(& $Config, $bid=BID, $rid=null, $mid=null, $setid=null)
    {
        return Config::saveConfig($Config, $bid, $rid, $mid, $setid);
    }

    /**
     * ToDo: deprecated method 2.7.0
     */
    function fix($Config)
    {
        return Config::fix($Config);
    }

    /**
     * ToDo: deprecated method 2.7.0
     */
    function setValide(&$Config, $rid=null, $mid=null, $setid=null)
    {
        $Config = Config::setValide($Config, $rid, $mid, $setid);
    }
}
