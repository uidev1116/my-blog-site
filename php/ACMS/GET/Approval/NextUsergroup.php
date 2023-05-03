<?php

class ACMS_GET_Approval_NextUsergroup extends ACMS_GET
{
    function get()
    {
        if (!sessionWithApprovalRequest()) {
            return false;
        }

        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        $DB = DB::singleton(dsn());
        $vars = array();
        $userGroup = array();
        $currentGroup = 0;

        if (editionIsEnterprise()) {
            $workflow = loadWorkflow(BID, CID);

            // 並列承認
            if ($workflow->get('workflow_type') === 'parallel') {
                return '';
            }
            //-----------------------------------------
            // ワークフローの逆承認順序でユーザグループを列挙
            $lastGroup = $workflow->getArray('workflow_last_group');
            foreach (array_reverse($workflow->getArray('workflow_route_group')) as $groupId) {
                $userGroup[] = $groupId;
            }
            $nextGroup = array();
            foreach ($userGroup as $ugid) {
                $SQL = SQL::newSelect('usergroup_user');
                $SQL->addSelect('usergroup_id');
                $SQL->addWhereOpr('usergroup_id', $ugid);
                $SQL->addWhereOpr('user_id', SUID);
                if ($group = $DB->query($SQL->get(dsn()), 'one')) {
                    $currentGroup = $group;
                    break;
                }
                $nextGroup = array($ugid);
            }
            if (empty($nextGroup)) {
                $nextGroup = $lastGroup;
            }
            if (empty($currentGroup)) {
                $startGroup = $workflow->getArray('workflow_start_group');
                foreach ($startGroup as $ugid) {
                    $SQL = SQL::newSelect('usergroup_user');
                    $SQL->addSelect('usergroup_id');
                    $SQL->addWhereOpr('usergroup_id', $ugid);
                    $SQL->addWhereOpr('user_id', SUID);
                    if ($group = $DB->query($SQL->get(dsn()), 'one')) {
                        $currentGroup = $group;
                        break;
                    }
                }
            }
            $vars['currentGroup'] = $currentGroup;

            if (!empty($nextGroup)) {
                $SQL = SQL::newSelect('usergroup');
                $SQL->addWhereIn('usergroup_id', $nextGroup);
                $all = $DB->query($SQL->get(dsn()), 'all');

                if (count($all) > 1) {
                    $nameAry = array();
                    foreach ($all as $row) {
                        $nameAry[] = $row['usergroup_name'];
                    }
                    $Tpl->add('group:loop', array(
                        'nextGroup' => 0,
                        'nextGroupName' => implode(', ', $nameAry),
                    ));
                }
                foreach ($all as $row) {
                    $Tpl->add('group:loop', array(
                        'nextGroup' => $row['usergroup_id'],
                        'nextGroupName' => $row['usergroup_name'],
                    ));
                }

                $SQL = SQL::newSelect('usergroup_user', 't_usergroup_user');
                $SQL->addLeftJoin('user', 'user_id', 'user_id', 't_user', 't_usergroup_user');
                $SQL->addWhereIn('usergroup_id', $nextGroup);
                $all = $DB->query($SQL->get(dsn()), 'all');

                foreach ($all as $user) {
                    $user['icon'] = loadUserIcon($user['user_id']);
                    $user['nextGroup'] = $user['usergroup_id'];
                    $userField = loadUserField($user['user_id']);
                    $user += $this->buildField($userField, $Tpl, 'user:loop');
                    $Tpl->add('user:loop', $user);
                }
            }
            $Tpl->add(null, $vars);
        } else {
            if (editionIsProfessional()) {
                $SQL = SQL::newSelect('user');
                $SQL->addLeftJoin('blog', 'blog_id', 'user_blog_id');
                if (config('blog_manage_approval') == 'on') {
                    ACMS_Filter::blogTree($SQL, BID, 'self-ancestor');
                } else {
                    $SQL->addWhereOpr('user_blog_id', BID);
                }
                ACMS_Filter::blogStatus($SQL);
                $SQL->addWhereIn('user_auth', array('editor', 'administrator'));

                $all = $DB->query($SQL->get(dsn()), 'all');

                $vars['currentGroup'] = 0;

                $Tpl->add('group:loop', array(
                    'nextGroup' => 0,
                    'nextGroupName' => '編集者, 管理者',
                ));

                foreach ($all as $user) {
                    $user['icon'] = loadUserIcon($user['user_id']);
                    $user['nextGroup'] = 0;
                    $userField = loadUserField($user['user_id']);
                    $user += $this->buildField($userField, $Tpl, 'user:loop');
                    $Tpl->add('user:loop', $user);
                }
                $Tpl->add(null, $vars);
            }
        }
        return $Tpl->get();
    }
}
