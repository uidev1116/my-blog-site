<?php

class ACMS_POST_Entry_Index_User extends ACMS_POST
{
    function post()
    {
        if (enableApproval(BID, CID)) {
            $this->Post->setMethod('entry', 'operative', sessionWithApprovalAdministrator(BID, CID));
        } elseif (roleAvailableUser()) {
            $this->Post->setMethod('entry', 'operative', roleAuthorization('entry_edit', BID));
        } else {
            $this->Post->setMethod('entry', 'operative', sessionWithCompilation());
        }
        $this->Post->setMethod('checks', 'required');
        $this->Post->setMethod('entry', 'uidIsNull', 1
            and !!($uid = intval($this->Post->get('uid')))
            and !!($bid = ACMS_RAM::userBlog($uid))
            and ACMS_RAM::blogLeft($bid) <= ACMS_RAM::blogLeft(BID)
            and ACMS_RAM::blogRight($bid) >= ACMS_RAM::blogRight(BID));
        $this->Post->validate(new ACMS_Validator());

        if ($this->Post->isValidAll()) {
            $DB = DB::singleton(dsn());
            $targetEIDs = [];
            foreach ($this->Post->getArray('checks') as $eid) {
                $id = preg_split('@:@', $eid, 2, PREG_SPLIT_NO_EMPTY);
                $bid = $id[0];
                $eid = $id[1];
                if (!($bid = intval($bid))) {
                    continue;
                }
                if (!($eid = intval($eid))) {
                    continue;
                }
                $SQL    = SQL::newUpdate('entry');
                $SQL->setUpdate('entry_user_id', $uid);
                $SQL->addWhereOpr('entry_id', $eid);
                $SQL->addWhereOpr('entry_blog_id', $bid);
                $DB->query($SQL->get(dsn()), 'exec');
                ACMS_RAM::entry($eid, null);
                $targetEIDs[] = $eid;
            }
            AcmsLogger::info('選択したエントリーのユーザーを「' . ACMS_RAM::userName($uid) . '」に変更しました', [
                'targetEIDs' => $targetEIDs,
            ]);
        } else {
            AcmsLogger::info('選択したエントリーのユーザー変更に失敗しました');
        }

        return $this->Post;
    }
}
