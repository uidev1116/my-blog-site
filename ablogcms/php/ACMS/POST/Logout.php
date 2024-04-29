<?php

class ACMS_POST_Logout extends ACMS_POST
{
    public $isCacheDelete  = false;

    public function post()
    {
        if (SUID) {
            $redirectUrl = Login::getLogoutRedirectUrl(SUID);
            AcmsLogger::info('ユーザー「' . ACMS_RAM::userName(SUID) . '」がログアウト処理をしました', [
                'suid' => SUID,
            ]);
            logout();

            $this->redirect($redirectUrl);
        }

        return $this->Post;
    }
}
