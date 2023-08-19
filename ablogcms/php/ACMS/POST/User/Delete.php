<?php

class ACMS_POST_User_Delete extends ACMS_POST_User
{
    function post()
    {
        $User = $this->extract('user');
        $User->reset();

        $this->Post->reset(true);
        $this->validate();

        if (!$this->Post->isValidAll()) {
            return $this->Post;
        }

        $this->delete();
        $this->Post->set('edit', 'delete');
        return $this->Post;
    }

    protected function validate(): void
    {
        $this->Post->setMethod(
            'user',
            'operable',
            !!UID && sessionWithAdministration() && UID !== SUID
        );
        $this->Post->setMethod(
            'user',
            'entryExists',
            !$this->entryExists(UID)
        );
        $this->Post->validate(new ACMS_Validator());
    }

    protected function entryExists(int $uid): bool
    {
        $sql = SQL::newSelect('entry');
        $sql->addWhereOpr('entry_user_id', $uid);
        $sql->setLimit(1);
        return !!DB::query($sql->get(dsn()));
    }

    protected function delete(): void
    {
        $userSql = SQL::newDelete('user');
        $userSql->addWhereOpr('user_id', UID);
        $userSql->addWhereOpr('user_blog_id', BID);
        DB::query($userSql->get(dsn()), 'exec');
        ACMS_RAM::user(UID, null);

        // 位置情報の削除
        $this->saveGeometry('uid', UID);

        // カスタムフィールドの削除
        Common::saveField('uid', UID);

        // フルテキストの削除
        Common::saveFulltext('uid', UID);

        // ユーザーセッションから削除
        $userSessionSql = SQL::newDelete('user_session');
        $userSessionSql->addWhereOpr('user_session_uid', UID);
        DB::query($userSessionSql->get(dsn()), 'exec');

        // 所属しているユーザーグループから削除
        $userGroupSql = SQL::newDelete('usergroup_user');
        $userGroupSql->addWhereOpr('user_id', UID);
        DB::query($userGroupSql->get(dsn()), 'exec');
    }
}
