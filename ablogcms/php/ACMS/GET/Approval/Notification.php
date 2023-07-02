<?php

class ACMS_GET_Approval_Notification extends ACMS_GET
{
    function notificationCount()
    {
        return Approval::notificationCount();
    }

    function buildSql()
    {
        return Approval::buildSql();
    }

    function get()
    {
        if (!editionWithProfessional()) return false;

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $DB     = DB::singleton(dsn());
        $vars   = array();

        $SQL    = $this->buildSql();
        $SQL->setOrder('approval_deadline_datetime', 'DESC');

        if ( !($all = $DB->query($SQL->get(dsn()), 'all')) ) {
            $Tpl->add('approval#notFound');
            return $Tpl->get();
        }

        $empty = true;
        foreach ( $all as $row ) {
            $exceptUsers    = explode(',', $row['notification_except_user_ids']);
            if ( in_array(strval(SUID), $exceptUsers)
            ) {
                continue;
            }
            if ( $row['notification_type'] == 'reject' ) {
                $requestUser = $row['notification_request_user_id'];

                $SQL    = SQL::newSelect('approval');
                $SQL->addWhereOpr('approval_type', 'request');
                $SQL->addWhereOpr('approval_revision_id', $row['notification_rev_id']);
                $SQL->addWhereOpr('approval_entry_id', $row['notification_entry_id']);
                $SQL->addWhereOpr('approval_request_user_id', SUID);
                $SQL->addWhereOpr('approval_datetime', $row['notification_datetime'], '<');

                if ( 0
                    || !$DB->query($SQL->get(dsn()), 'row')
                    || ACMS_RAM::entryStatus($row['notification_entry_id']) === 'close'
                    || ACMS_RAM::entryStatus($row['notification_entry_id']) === 'trash'
                ) {
                    continue;
                }
            }

            //--------------
            // 操作ユーザ情報
            $reqUserField   = loadUser($row['approval_request_user_id']);
            $reqUser        = $this->buildField($reqUserField, $Tpl, array('requestUser', 'approval:loop'));
            $Tpl->add(array('requestUser', 'approval:loop'), $reqUser);

            //------------------
            // 担当者 承認依頼のみ
            $receive = array();
            if ( $row['approval_type'] === 'request' ) {
                $receive['deadline']    = $row['approval_deadline_datetime'];
                if ( !!$row['approval_receive_user_id'] ) {
                    $receive['userOrGroup'] = ACMS_RAM::userName($row['approval_receive_user_id']);
                } else if ( !!$row['approval_receive_usergroup_id'] ) {
                    $SQL    = SQL::newSelect('usergroup');
                    $SQL->addSelect('usergroup_name');
                    $SQL->addWhereOpr('usergroup_id', $row['approval_receive_usergroup_id']);
                    $groupName = $DB->query($SQL->get(dsn()), 'one');
                    $receive['userOrGroup'] = $groupName;
                }
                $Tpl->add(array('receiveUser', 'approval:loop'), $receive);
            }

            //---------
            // 承認情報
            $approvalField  = new Field();
            foreach ( $row as $key => $val ) {
                $key_       = substr($key, strlen('approval_'));
                $approvalField->add($key_, $val);
            }

            $SQL    = SQL::newSelect('entry_rev');
            $SQL->addSelect('entry_status');
            $SQL->addWhereOpr('entry_rev_id', $row['notification_rev_id']);
            $SQL->addWhereOpr('entry_id', $row['notification_entry_id']);
            $SQL->addWhereOpr('entry_blog_id', $row['notification_blog_id']);
            if ( $revStatus = $DB->query($SQL->get(dsn()), 'one') ) {
                if ( $approvalField->get('type') === 'request' && $revStatus === 'trash' ) {
                    $approvalField->set('type', 'trash');
                }
            }

            $approval   = $this->buildField($approvalField, $Tpl, array('approval:loop'));
            $approval   += $receive;

            if ( 1
                && isset($approval['deadline'])
                && strtotime(date('Y-m-d')) >= strtotime($approval['deadline'])
            ) {
                $approval['expired'] = ' class="acms-table-danger"';
            }

            $approval['rev_id']         = $row['notification_rev_id'];
            $approval['entry_id']       = $row['notification_entry_id'];
            $approval['blog_id']        = $row['notification_blog_id'];
            $approval['approval_id']    = $row['notification_approval_id'];

            $approval['url'] = acmsLink(array(
                'bid'           => $row['approval_blog_id'],
                'eid'           => $row['notification_entry_id'],
                'tpl'           => 'ajax/revision-preview.html',
                'query'         => array(
                    'rvid'  => $row['notification_rev_id'],
                ),
            ), false, false, true);

            $Tpl->add('approval:loop', $approval);
            $empty = false;
        }
        if ( $empty ) {
            $Tpl->add('approval#notFound');
            return $Tpl->get();
        }
        $Tpl->add(null, $vars);

        return $Tpl->get();
    }
}
