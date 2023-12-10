<?php

class ACMS_POST_Member_Admin_ResetPasswordAuth extends ACMS_POST_Member_ResetPasswordAuth
{
    /**
     * 権限の限定
     *
     * @return array
     */
    protected function limitedAuthority(): array
    {
        return Login::getAdminLoginAuth();
    }
}
