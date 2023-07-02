<?php

use Acms\Services\Facades\Preview;

class ACMS_POST_Timemachine_Enable extends ACMS_POST
{
    function post()
    {
        if (!timemachineAuth()) {
            die('NG');
        }
        $session =& Field::singleton('session');
        $date = $this->Post->get('date', false);
        $time = $this->Post->get('time', false);
        $ruleId = $this->Post->get('rule', false);
        $fakeUa = $this->Post->get('preview_fake_ua', false);
        $token = $this->Post->get('preview_token', false);
        $datetime = $date . ' ' . $time;
        if (1
            && preg_match(REGEXP_VALID_DATETIME, $datetime)
            && $fakeUa
            && $token
        ) {
            $session->set('timemachine_datetime', $datetime);
            if ($ruleId) {
                $session->set('timemachine_rule_id', $ruleId);
            }
            Preview::startPreviewMode($fakeUa, $token);
            die('OK');
        }
        die('NG');
    }
}
