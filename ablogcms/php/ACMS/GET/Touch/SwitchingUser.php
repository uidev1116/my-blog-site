<?php

class ACMS_GET_Touch_SwitchingUser extends ACMS_GET
{
    function get()
    {
        if ($this->getOriginalUserId()) {
            return $this->tpl;
        }
        return false;
    }

    /**
     * Get original user id.
     *
     * @return bool
     */
    protected function getOriginalUserId()
    {
        $session = Session::handle();
        if ($uid = $session->get(ACMS_LOGIN_SESSION_ORGINAL_UIR)) {
            return $uid;
        }
        return false;
    }
}
