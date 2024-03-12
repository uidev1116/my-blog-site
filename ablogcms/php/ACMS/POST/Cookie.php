<?php

class ACMS_POST_Cookie extends ACMS_POST
{
    public $isCacheDelete = false;

    function post()
    {
        $Meta = $this->extract('meta');
        $lifetime = intval($Meta->get('lifetime'));
        $expire = $lifetime ? (REQUEST_TIME + $lifetime) : null;
        $Cookie = $this->extract('cookie');
        foreach ($Cookie->listFields() as $key) {
            if ($Cookie->isNull($key)) {
                foreach (array($key . '[0]', $key) as $_key) {
                    acmsSetCookie($_key, null, REQUEST_TIME - 1, '/');
                }
            } else {
                foreach ($Cookie->getArray($key) as $i => $val) {
                    $_expire = $expire;
                    if (empty($val)) {
                        $val = null;
                        $_expire = REQUEST_TIME - 1;
                    }
                    acmsSetCookie($key . '[' . $i . ']', $val, $_expire, '/');
                }
            }
        }
        $this->redirect(REQUEST_URL);
    }
}
