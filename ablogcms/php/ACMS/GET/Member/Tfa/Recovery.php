<?php

class ACMS_GET_Member_Tfa_Recovery extends ACMS_GET_Member
{
    /**
     * テンプレート組み立て
     *
     * @param Template $tpl
     * @return void
     */
    protected function buildTpl(Template $tpl): void
    {
        $loginField = $this->Post->getChild('login');
        $vars = [
            'trialTime' => config('login_trial_time', 5),
            'trialNumber' => config('login_trial_number', 5),
            'lockTime' => config('login_lock_time', 5),
        ];
        if ($loginField->get('tfaRecovery') === 'success') {
            $tpl->add('success'); // 2段階認証無効化成功
        } else {
            $vars += $this->buildField($this->Post, $tpl);
            $tpl->add('notSuccessful', $vars);
        }
        $tpl->add(null, $vars);
    }
}
