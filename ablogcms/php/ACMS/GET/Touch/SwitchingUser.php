<?php

class ACMS_GET_Touch_SwitchingUser extends ACMS_GET
{
    public function get()
    {
        if ($this->getOriginalUserId()) {
            return $this->tpl;
        }
        return '';
    }

    /**
     * Get original user id.
     *
     * @return bool
     */
    protected function getOriginalUserId()
    {
        $session = Session::handle();
        if ($uid = $session->get(ACMS_LOGIN_SESSION_ORGINAL_UID)) {
            return $uid;
        }
        return false;
    }
}
