<?php

class ACMS_POST_User_Index_Auth extends ACMS_POST_User
{
    function post()
    {
        $this->Post->setMethod('user', 'operative', sessionWithAdministration());
        $this->Post->setMethod('checks', 'required');
        $this->Post->setMethod('auth', 'required');
        $this->Post->setMethod('auth', 'in', array(
            'administrator',
            'editor',
            'contributor',
            'subscriber',
        ));
        $this->Post->setMethod('user', 'limit', $this->isLimit());
        $this->Post->validate(new ACMS_Validator());

        if ($this->Post->isValidAll()) {
            $DB = DB::singleton(dsn());
            $auth = $this->Post->get('auth');
            $targetUsers = [];

            foreach ( $this->Post->getArray('checks') as $uid ) {
                if ( !($uid = intval($uid)) ) continue;
                $SQL    = SQL::newUpdate('user');
                $SQL->setUpdate('user_auth', $auth);
                $SQL->addWhereOpr('user_id', $uid);
                $SQL->addWhereOpr('user_blog_id', BID);
                $DB->query($SQL->get(dsn()), 'exec');
                ACMS_RAM::user($uid, null);

                $targetUsers[] = ACMS_RAM::userName($uid) . '（' . ACMS_RAM::userMail($uid) . '）';
            }
            if (!empty($targetUsers)) {
                $authName = '';
                if ($auth === 'subscriber') $authName = '読者';
                if ($auth === 'contributor') $authName = '投稿者';
                if ($auth === 'editor') $authName = '編集者';
                if ($auth === 'administrator') $authName = '管理者';
                AcmsLogger::info('選択したユーザーの権限を「' . $authName . '」に変更しました', $targetUsers);
            }
        }

        return $this->Post;
    }

    function isLimit($update=false)
    {
        // 読者への変更であれば制限なし
        if ( $this->Post->get('auth') === 'subscriber' ) {
            return true;
        }

        // 現在読者（昇格予定のユーザー）の数をカウントする
        $DB  = DB::singleton(dsn());
        $SQL = SQL::newSelect('user');
        $SQL->addSelect('*', 'count', null, 'COUNT');
        $SQL->addWhereIn('user_id', $this->Post->getArray('checks'));
        $SQL->addWhereOpr('user_auth', 'subscriber');
        $add_amount = $DB->query($SQL->get(dsn()), 'one');

        // 現在の制限数対象のユーザー
        $now_amount = $this->countOfLimitedAuthUsers();

        return LICENSE_BLOG_LIMIT >= ($add_amount + $now_amount);
    }
}
