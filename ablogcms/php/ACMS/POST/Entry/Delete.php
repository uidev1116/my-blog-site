<?php

class ACMS_POST_Entry_Delete extends ACMS_POST_Entry
{
    function delete($eid)
    {
        Entry::entryDelete($eid);
        Entry::revisionDelete($eid);

        return true;
    }

    function post()
    {
        $this->Post->reset(true);
        if (roleAvailableUser()) {
            $this->Post->setMethod('entry', 'operable', (1
                and !!($eid = intval($this->Post->get('eid', EID)))
                and !!($ebid = ACMS_RAM::entryBlog($eid))
                and roleAuthorization('entry_delete', $ebid, $eid)
            ));
        } else {
            $this->Post->setMethod('entry', 'operable', (1
                and !!($eid = intval($this->Post->get('eid', EID)))
                and !!($ebid = ACMS_RAM::entryBlog($eid))
                and ACMS_RAM::blogLeft(SBID) <= ACMS_RAM::blogLeft($ebid)
                and ACMS_RAM::blogRight(SBID) >= ACMS_RAM::blogRight($ebid)
                and ( 0
                    or sessionWithCompilation()
                    or ( 1
                        and sessionWithContribution()
                        and SUID == ACMS_RAM::entryUser($eid)
                    )
                )
            ));
        }
        $this->Post->validate();

        if ($this->Post->isValidAll()) {
            if (HOOK_ENABLE) {
                Webhook::call(BID, 'entry', 'entry:deleted', array($eid, null));
            }
            $entryTitle = ACMS_RAM::entryTitle($eid);
            $this->delete($eid);
            $redirect   = $this->Post->get('redirect');

            AcmsLogger::info('「' . $entryTitle . '」エントリーを削除しました');

            if (!empty($redirect) && Common::isSafeUrl($redirect)) {
                $this->redirect($redirect);
            } else {
                $this->redirect(acmsLink(array(
                    'bid'   => BID,
                    'cid'   => CID,
                    'eid'   => '',
                )));
            }
        }
        return $this->Post;
    }
}
