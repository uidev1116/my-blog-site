<?php

class ACMS_POST_User_UpdatePassword extends ACMS_POST_User_Update
{
    public function post()
    {
        $this->user = $this->extract('user');
        $this->validate();

        if ($this->Post->isValidAll()) {
            $this->updatePassword();
            $this->Post->set('edit', 'update');

            AcmsLogger::info('ユーザー「' . ACMS_RAM::userName(UID) . '」のパスワードを変更しました', [
                'uid' => UID,
            ]);
        } else {
            AcmsLogger::info('ユーザー「' . ACMS_RAM::userName(UID) . '」のパスワード変更に失敗しました', [
                'uid' => UID,
                'pass' => $this->user->_aryV,
            ]);
        }
        return $this->Post;
    }

    protected function validate()
    {
        $this->user->setMethod('pass', 'required');
        $this->user->setMethod('pass', 'password');
        $this->user->setMethod('user', 'operable', $this->isOperable());
        $this->user->validate(new ACMS_Validator());
    }

    protected function updatePassword()
    {
        $DB = DB::singleton(dsn());
        $SQL = SQL::newUpdate('user');
        $SQL->addUpdate('user_pass', acmsUserPasswordHash($this->user->get('pass')));
        $SQL->addUpdate('user_pass_generation', PASSWORD_ALGORITHM_GENERATION);
        $SQL->addWhereOpr('user_id', UID);
        $SQL->addWhereOpr('user_blog_id', BID);
        $DB->query($SQL->get(dsn()), 'exec');
        ACMS_RAM::user(UID, null);
    }
}
