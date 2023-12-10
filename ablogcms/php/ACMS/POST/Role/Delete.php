<?php

class ACMS_POST_Role_Delete extends ACMS_POST
{
    public function post()
    {
        $rid = intval($this->Get->get('rid'));

        $this->validate($rid);

        if (!$this->Post->isValidAll() ) {
            AcmsLogger::info('ロールの削除に失敗しました', [
                'roleID' => $rid,
            ]);
            return $this->Post;
        }

        $role = loadRole($rid);
        $this->delete($rid);
        $this->Post->set('edit', 'delete');

        AcmsLogger::info('「' . $role->get('name') . '」ロールを削除しました', [
            'roleID' => $rid,
        ]);

        return $this->Post;
    }

    protected function validate(int $rid): void
    {
        $this->Post->setMethod(
            'role',
            'operable',
            (
                $rid > 0 &&
                sessionWithEnterpriseAdministration() &&
                BID === RBID
            )
        );
        $this->Post->setMethod(
            'role',
            'userGroupExists',
            !$this->userGroupExists($rid)
        );

        $this->Post->validate(new ACMS_Validator());
    }

    protected function delete(int $rid): void
    {
        $roleSql = SQL::newDelete('role');
        $roleSql->addWhereOpr('role_id', $rid);
        DB::query($roleSql->get(dsn()), 'exec');

        $roleBlogSql = SQL::newDelete('role_blog');
        $roleBlogSql->addWhereOpr('role_id', $rid);
        DB::query($roleBlogSql->get(dsn()), 'exec');
    }

    protected function userGroupExists(int $rid): bool
    {
        $sql = SQL::newSelect('usergroup');
        $sql->addWhereOpr('usergroup_role_id', $rid);
        $sql->setLimit(1);
        return !!DB::query($sql->get(dsn()), 'one');
    }
}
