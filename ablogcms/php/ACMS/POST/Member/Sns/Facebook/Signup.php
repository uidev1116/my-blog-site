<?php

class ACMS_POST_Member_Sns_Facebook_Signup extends ACMS_POST_Member_Sns_Facebook_Signin
{
    /**
     * アクションを設定（signin|admin-login|signup|register）
     * @return string
     */
    protected function getActionName(): string
    {
        return 'signup';
    }
}
