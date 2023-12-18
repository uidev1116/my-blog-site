<?php

class ACMS_GET_Admin_Member_Index extends ACMS_GET_Admin_User_Index
{
    /**
     * 権限で絞り込み
     *
     * @param SQL_Select $sql
     * @return void
     */
    protected function filterAuth(SQL_Select $sql): void
    {
        if (config('subscribe_auth') === 'contributor') {
            $sql->addWhereIn('user_auth', ['subscriber', 'contributor']);
        } else {
            $sql->addWhereOpr('user_auth', 'subscriber');
        }
    }
}
