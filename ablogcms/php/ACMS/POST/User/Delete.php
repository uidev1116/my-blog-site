<?php

use Acms\Services\Facades\Login;
use Acms\Services\Facades\Application;
use Acms\Services\Facades\Webhook;

class ACMS_POST_User_Delete extends ACMS_POST_User
{
    public function post()
    {
        if (is_null(UID)) {
            return $this->Post;
        }
        $User = $this->extract('user');
        $User->reset();

        $this->Post->reset(true);
        $this->validate();

        if (!$this->Post->isValidAll()) {
            return $this->Post;
        }

        Webhook::call(BID, 'user', ['user:deleted'], UID);

        $name = ACMS_RAM::userName(UID);
        $this->delete();
        $this->Post->set('edit', 'delete');

        AcmsLogger::info('ユーザー「' . $name . '」を削除しました', [
            'uid' => UID,
        ]);

        return $this->Post;
    }

    protected function validate(): void
    {
        $userService = Application::make('user');

        $this->Post->setMethod(
            'user',
            'operable',
            !!UID && sessionWithAdministration() && UID !== SUID
        );
        $this->Post->setMethod(
            'user',
            'entryExists',
            !$userService->entryExists(UID)
        );
        $this->Post->validate(new ACMS_Validator());
    }

    protected function delete(): void
    {
        $userService = Application::make('user');
        $userService->physicalDelete(UID);
    }
}
