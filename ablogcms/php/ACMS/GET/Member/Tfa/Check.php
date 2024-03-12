<?php

use Acms\Services\Facades\Tfa;

class ACMS_GET_Member_Tfa_Check extends ACMS_GET_Member_Signup
{
    /**
     * 初期処理
     *
     * @return void
     */
    protected function init(): void
    {
    }

    /**
     * テンプレート組み立て
     *
     * @param Template $tpl
     * @return void
     */
    protected function buildTpl(Template $tpl): void
    {
        if (!SUID || !Tfa::isAvailable()) {
            return;
        }
        $vars = [];

        if ($secret = Tfa::getSecretKey(SUID)) {
            // 登録済み
            $vars['step'] = 'registered';
        } else {
            // 未登録
            $vars['step'] = 'unregistered';
        }
        $tpl->add(null, $vars);
    }
}
