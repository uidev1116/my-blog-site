<?php

use Acms\Services\Facades\Session;

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
     * @return bool|int
     */
    protected function getOriginalUserId()
    {
        if (SUID) { // @phpstan-ignore-line
            $session = Session::handle();
            if ($uid = $session->get(ACMS_LOGIN_SESSION_ORGINAL_UID)) {
                return intval($uid);
            }
        }
        return false;
    }
}
