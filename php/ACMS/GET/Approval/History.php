<?php

class ACMS_GET_Approval_History extends ACMS_GET
{
    function get()
    {
        if ( !enableApproval() ) return false;
        if ( !RVID ) return false;

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $DB     = DB::singleton(dsn());
        $vars   = array();

        $SQL    = SQL::newSelect('entry_rev');
        $SQL->addWhereOpr('entry_id', EID);
        $SQL->addWhereOpr('entry_rev_id', RVID);
        $SQL->addWhereOpr('entry_blog_id', BID);
        if ( $entry = $DB->query($SQL->get(dsn()), 'row') ) {
            foreach ( $entry as $key => $val ) {
                $vars[$key] = $val;
            }
        }

        $SQL    = SQL::newSelect('approval');
        $SQL->addSelect('approval_id', 'count', null, 'COUNT');
        $SQL->addSelect('approval_id');
        $SQL->addSelect('approval_type');
        $SQL->addSelect('approval_method');
        $SQL->addSelect('approval_datetime');
        $SQL->addSelect('approval_deadline_datetime');
        $SQL->addSelect('approval_comment');
        $SQL->addSelect('approval_request_usergroup_id');
        $SQL->addSelect('approval_receive_usergroup_id');
        $SQL->addSelect('approval_request_user_id');
        $SQL->addSelect('approval_receive_user_id');
        $SQL->addSelect('approval_revision_id');
        $SQL->addSelect('approval_entry_id');
        $SQL->addSelect('approval_blog_id');
        $SQL->addWhereOpr('approval_revision_id', RVID);
        $SQL->addWhereOpr('approval_entry_id', EID);
        $SQL->addWhereOpr('approval_blog_id', BID);
        $SQL->setGroup('approval_id');
        $SQL->setOrder('approval_datetime', 'DESC');
        if ( !($all = $DB->query($SQL->get(dsn()), 'all')) ) return '';

        foreach ( $all as $row ) {
            //--------------
            // 操作ユーザ情報
            $reqUserField   = loadUser($row['approval_request_user_id']);
            $reqUser        = $this->buildField($reqUserField, $Tpl, array('requestUser', 'approval:loop'));

            $Tpl->add(array('requestUser', 'approval:loop'), $reqUser);

            //------------------
            // 担当者 承認依頼のみ
            if ( $row['approval_type'] === 'request' ) {
                $receive['deadline']    = $row['approval_deadline_datetime'];
                if ( !!$row['approval_receive_user_id'] ) {
                    $receive['userOrGroupp'] = ACMS_RAM::userName($row['approval_receive_user_id']);
                } else if ( !!$row['approval_receive_usergroup_id'] ) {
                    $SQL    = SQL::newSelect('usergroup');
                    $SQL->addSelect('usergroup_name');
                    $SQL->addWhereOpr('usergroup_id', $row['approval_receive_usergroup_id']);
                    $groupName = $DB->query($SQL->get(dsn()), 'one');
                    $receive['userOrGroupp'] = $groupName;
                }
                if ( intval($row['count']) > 1 ) {
                    $receive['userOrGroupp'] = '次グループ全体';
                }
                $Tpl->add(array('receiveUser', 'approval:loop'), $receive);
            }

            //---------
            // 承認情報
            $approvalField  = new Field();
            foreach ( $row as $key => $val ) {
                $key_ = substr($key, strlen('approval_'));
                if ($key_ === 'comment') {
                    $val = str_replace(array('{', '}'), array('\\{', '\\}'), $val);
                }
                $approvalField->add($key_, $val);
            }

            $SQL    = SQL::newSelect('entry_rev');
            $SQL->addSelect('entry_status');
            $SQL->addWhereOpr('entry_rev_id', RVID);
            $SQL->addWhereOpr('entry_id', EID);
            $SQL->addWhereOpr('entry_blog_id', BID);
            if ( $revStatus = $DB->query($SQL->get(dsn()), 'one') ) {
                if ( $approvalField->get('type') === 'request' && $revStatus === 'trash' ) {
                    $approvalField->set('type', 'trash');
                }
            }
            $approval  = $this->buildField($approvalField, $Tpl, array('approval:loop'));

            $Tpl->add('approval:loop', $approval);
        }
        $Tpl->add(null, $vars);

        return $Tpl->get();
    }
}
