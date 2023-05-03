<?php

class ACMS_POST_2GET extends ACMS_POST
{
    var $isCacheDelete  = false;

    protected $isCSRF = false;

    function post()
    {
        $Post   = new Field($this->Post);
        if ($Post->get('nocache') === 'yes') {
            $Post->add('query', 'nocache');
        }
        return $this->redirect(acmsLink(Common::getUriObject($Post), true, true));
    }
}
