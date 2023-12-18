<?php

class ACMS_POST_Member_Tfa_Auth extends ACMS_POST_Member_Signin
{
    /**
     * 2段階認証のアクション
     * 戻り値が true だと、そこで処理をやめる
     *
     * @param Field_Validation $loginField
     * @param int $uid
     * @return bool
     */
    protected function checkTowFactorAuthAction(Field_Validation $loginField, int $uid): bool
    {
        $inputCode = preg_replace("/(\s|　)/", "", $loginField->get('code'));

        if (Tfa::verifyAccount($uid, $inputCode)) {
            AcmsLogger::info('2段階認証に成功しました', [
                'uid' => $uid,
            ]);
            return false; // 認証OK
        }
        // 認証NG
        $loginField->setMethod('code', 'auth', false);
        $loginField->validate(new ACMS_Validator());

        AcmsLogger::info('2段階認証に失敗しました', [
            'uid' => $uid,
        ]);

        return true;
    }
}
