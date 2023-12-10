<?php

class ACMS_POST_Logout extends ACMS_POST
{
    var $isCacheDelete  = false;

    function post()
    {
        if (SUID) {
            AcmsLogger::info('ユーザー「' . ACMS_RAM::userName(SUID) . '」がログアウト処理をしました', [
                'suid' => SUID,
            ]);
            logout();
        }

        $this->redirect(acmsLink(array(
            'bid' => BID,
        ), false), false);
    }
}
