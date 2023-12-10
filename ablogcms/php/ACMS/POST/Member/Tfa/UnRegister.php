<?php

class ACMS_POST_Member_Tfa_Unregister extends ACMS_POST_Member
{
    /**
     * 2段階認証を無効化
     *
     * @return Field_Validation
     */
    public function post(): Field_Validation
    {
        $tfaField = $this->extract('tfa');
        $this->validate($tfaField);
        $this->disableTfa(SUID);

        if ($this->Post->isValidAll()) {
            $this->Post->set('register', 'success');
            AcmsLogger::info('2段階認証の無効化をしました', [
                'uid' => SUID,
                'name' => ACMS_RAM::userName(SUID),
            ]);
        }
        return $this->Post;
    }

    /**
     * 2段階認証有効化のバリデーション
     *
     * @param Field_Validation $tfaField
     * @return void
     */
    protected function validate(Field_Validation $tfaField): void
    {
        if (!SUID) {
            $tfaField->setMethod('tfa', 'isOperable', false);
        }
        if (!Tfa::isAvailable()) {
            $tfaField->setMethod('tfa', 'isOperable', false);
        }
        $tfaField->validate(new ACMS_Validator());
    }

    /**
     * 2段階認証を無効化
     *
     * @param int $uid
     * @return void
     */
    protected function disableTfa(int $uid): void
    {
        $sql = SQL::newUpdate('user');
        $sql->addUpdate('user_tfa_secret', null);
        $sql->addUpdate('user_tfa_secret_iv', null);
        $sql->addUpdate('user_tfa_recovery', null);
        $sql->addWhereOpr('user_id', $uid);
        DB::query($sql->get(dsn()), 'exec');
        ACMS_RAM::user($uid, null);
    }
}
