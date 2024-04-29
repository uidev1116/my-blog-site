<?php

class ACMS_POST_User_Index_Status extends ACMS_POST
{
    function post()
    {
        $this->Post->setMethod('user', 'operative', sessionWithAdministration());
        $this->Post->setMethod('checks', 'required');
        $this->Post->setMethod('status', 'required');
        $this->Post->setMethod('status', 'in', ['open', 'close']);
        $this->Post->validate(new ACMS_Validator());

        if ($this->Post->isValidAll()) {
            $DB = DB::singleton(dsn());
            $status = $this->Post->get('status');
            $targetUsers = [];
            foreach ($this->Post->getArray('checks') as $uid) {
                if (!($uid = intval($uid))) {
                    continue;
                }
                $SQL = SQL::newUpdate('user');
                $SQL->setUpdate('user_status', $status);
                $SQL->addWhereOpr('user_id', $uid);
                $SQL->addWhereOpr('user_blog_id', BID);
                $DB->query($SQL->get(dsn()), 'exec');
                ACMS_RAM::user($uid, null);

                $targetUsers[] = ACMS_RAM::userName($uid) . '（' . ACMS_RAM::userMail($uid) . '）';
            }
            if (!empty($targetUsers)) {
                AcmsLogger::info('選択したユーザーのステータスを「' . $status . '」に変更しました', $targetUsers);
            }
        }

        return $this->Post;
    }
}
