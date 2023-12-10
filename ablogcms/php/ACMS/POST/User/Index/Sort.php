<?php

class ACMS_POST_User_Index_Sort extends ACMS_POST
{
    function post()
    {
        $this->Post->setMethod('user', 'operative', sessionWithAdministration());
        $this->Post->setMethod('checks', 'required');
        $this->Post->validate(new ACMS_Validator());

        if ( $this->Post->isValidAll() ) {
            $DB = DB::singleton(dsn());
            $targetUIDs = [];
            foreach ( $this->Post->getArray('checks') as $uid ) {
                if ( !($uid = intval($uid)) ) continue;
                if ( !($sort = intval($this->Post->get('sort-'.$uid))) ) $sort = 1;

                $SQL    = SQL::newUpdate('user');
                $SQL->setUpdate('user_sort', $sort);
                $SQL->addWhereOpr('user_id', $uid);
                $SQL->addWhereOpr('user_blog_id', BID);
                $DB->query($SQL->get(dsn()), 'exec');
                ACMS_RAM::user($uid, null);

                $targetUIDs[] = $uid;
            }

            $sql = SQL::newSelect('user');
            $sql->addWhereIn('user_id', $targetUIDs);
            $sql->addWhereOpr('user_blog_id', BID);
            $sql->setOrder('user_sort', 'ASC');
            $users = DB::query($sql->get(dsn()), 'all');

            $result = [];
            foreach ($users as $user) {
                $result[] = [
                    'uid' => $user['user_id'],
                    'name' => $user['user_name'],
                    'mail' => $user['user_mail'],
                    'sort' => $user['user_sort'],
                ];
            }
            if (!empty($result)) {
                AcmsLogger::info('選択したユーザーの並び順を変更しました', $result);
            }
        }

        return $this->Post;
    }
}
