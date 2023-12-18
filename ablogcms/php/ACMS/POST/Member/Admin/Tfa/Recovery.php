<?php

class ACMS_POST_Member_Admin_Tfa_Recovery extends ACMS_POST_Member_Tfa_Recovery
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

    /**
     * アクセス制限のチェック
     *
     * @return bool
     */
    protected function accessRestricted(): bool
    {
        return Login::accessRestricted(true);
    }
}
