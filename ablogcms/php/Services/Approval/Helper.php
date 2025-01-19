<?php

namespace Acms\Services\Approval;

use DB;
use SQL;
use ACMS_RAM;
use ACMS_Filter;

class Helper
{
    /**
     * 通知数をカウント
     *
     * @return int
     */
    public function notificationCount()
    {
        $SQL = $this->buildSql();

        if (!($all = DB::query($SQL->get(dsn()), 'all'))) {
            return 0;
        }
        $count = 0;
        foreach ($all as $row) {
            $exceptUsers = explode(',', $row['notification_except_user_ids']);
            if (in_array(strval(SUID), $exceptUsers, true)) {
                continue;
            }
            if ($row['notification_type'] == 'reject') {
                $SQL    = SQL::newSelect('approval');
                $SQL->addWhereOpr('approval_type', 'request');
                $SQL->addWhereOpr('approval_revision_id', $row['notification_rev_id']);
                $SQL->addWhereOpr('approval_entry_id', $row['notification_entry_id']);
                $SQL->addWhereOpr('approval_request_user_id', SUID);
                $SQL->addWhereOpr('approval_datetime', $row['notification_datetime'], '<');

                if (
                    0
                    || !DB::query($SQL->get(dsn()), 'row')
                    || ACMS_RAM::entryStatus($row['notification_entry_id']) === 'close'
                    || ACMS_RAM::entryStatus($row['notification_entry_id']) === 'trash'
                ) {
                    continue;
                }
            }
            $count++;
        }
        return $count;
    }

    /**
     * 通知の絞り込み
     *
     * @return \SQL_Select
     */
    public function buildSql()
    {
        $SQL = SQL::newSelect('approval_notification');
        $SQL->addLeftJoin('approval', 'notification_approval_id', 'approval_id');
        $SQL->addLeftJoin('blog', 'blog_id', 'notification_blog_id');
        $SQL->addInnerJoin('entry_rev', 'notification_rev_id', 'entry_rev_id');
        $SQL->addWhereOpr('notification_entry_id', SQL::newField('entry_id'));
        $WHERE = SQL::newWhere();

        if (editionIsEnterprise()) {
            $groupsList = $this->getGroupList(SUID);
            // reject
            $W = SQL::newWhere();
            $W->addWhereOpr('notification_type', 'reject', '=', 'OR');
            $WHERE->addWhere($W, 'OR');
            // request (parallel)
            $W2 = SQL::newWhere();
            $W2->addWhereOpr('notification_request_user_id', SUID, '<>', 'AND');
            $W2->addWhereOpr('approval_method', 'parallel', '=', 'AND');
            $WHERE->addWhere($W2, 'OR');
            // request (series)
            $W3 = SQL::newWhere();
            $W3->addWhereOpr('notification_receive_user_id', null, '=', 'AND');
            $W3->addWhereOpr('approval_method', 'series', '=', 'AND');
            $W3->addWhereIn('notification_receive_usergroup_id', $groupsList, 'AND');
            $WHERE->addWhere($W3, 'OR');
            $WHERE->addWhereOpr('notification_receive_user_id', SUID, '=', 'OR');
        } elseif (editionIsProfessional()) {
            if (isSessionContributor()) {
                $SQL->addWhereOpr('notification_type', 'request', '<>');
            }
            if (config('blog_manage_approval') === 'on') {
                ACMS_Filter::blogTree($SQL, ACMS_RAM::userBlog(SUID), 'self-descendant');
            } else {
                $SQL->addWhereOpr('user_blog_id', ACMS_RAM::userBlog(SUID));
            }

            $W = SQL::newWhere();
            $W->addWhereOpr('notification_type', 'reject', '=', 'OR');
            $W->addWhereOpr('notification_receive_user_id', SUID, '=', 'OR');
            $WHERE->addWhere($W, 'OR');

            $W2 = SQL::newWhere();
            $W2->addWhereOpr('notification_receive_user_id', null);
            $W2->addWhereOpr('notification_receive_usergroup_id', null);
            $WHERE->addWhere($W2, 'OR');
        }
        $SQL->addWhere($WHERE);


        return $SQL;
    }

    /**
     * @param int|null $uid
     * @return array
     */
    protected function getGroupList($uid = SUID)
    {
        $groupsList = [];
        if (empty($uid)) {
            return $groupsList;
        }
        $SQL = SQL::newSelect('usergroup_user');
        $SQL->addWhereOpr('user_id', $uid);
        $groups = DB::query($SQL->get(dsn()), 'all');
        foreach ($groups as $val) {
            $groupsList[] = $val['usergroup_id'];
            ;
        }
        return $groupsList;
    }
}
