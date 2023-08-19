<?php

class ACMS_POST_Usergroup_Delete extends ACMS_POST
{
    public function post()
    {
        $ugid = intval($this->Get->get('ugid'));

        $this->validate($ugid);

        if (!$this->Post->isValidAll()) {
            return $this->Post;
        }

        $this->delete($ugid);
        $this->Post->set('edit', 'delete');

        return $this->Post;
    }

    protected function validate(int $ugid): void
    {
        $this->Post->setMethod(
            'usergroup',
            'operable',
            (
                $ugid > 0 &&
                sessionWithEnterpriseAdministration() &&
                BID === RBID
            )
        );
        $this->Post->setMethod(
            'usergroup',
            'workflowExists',
            !$this->workflowExists($ugid)
        );
        $this->Post->validate(new ACMS_Validator());
    }

    protected function delete(int $ugid): void
    {
        $userGroupSql = SQL::newDelete('usergroup');
        $userGroupSql->addWhereOpr('usergroup_id', $ugid);
        DB::query($userGroupSql->get(dsn()), 'exec');

        $userGroupUserSql = SQL::newDelete('usergroup_user');
        $userGroupUserSql->addWhereOpr('usergroup_id', $ugid);
        DB::query($userGroupUserSql->get(dsn()), 'exec');
    }

    protected function workflowExists(int $ugid): bool
    {
        $sql1 = SQL::newSelect('workflow');
        $where = SQL::newWhere();
        $where->addWhere(SQL::newFunction($ugid . ', workflow_start_group', 'FIND_IN_SET'), 'OR');
        $where->addWhere(SQL::newFunction($ugid . ', workflow_last_group', 'FIND_IN_SET'), 'OR');
        $sql1->addWhere($where);
        $sql1->setLimit(1);

        if (!!DB::query($sql1->get(dsn()), 'one')) {
            return true;
        }

        $sql2 = SQL::newSelect('workflow_usergroup');
        $sql2->addWhereOpr('usergroup_id', $ugid);
        $sql2->setLimit(1);

        if (!!DB::query($sql2->get(dsn()))) {
            return true;
        }

        return false;
    }
}
