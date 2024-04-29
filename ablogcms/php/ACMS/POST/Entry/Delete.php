<?php

class ACMS_POST_Entry_Delete extends ACMS_POST_Entry
{
    /**
     * @param int $entryId
     * @return true
     */
    protected function delete(int $entryId): bool
    {
        Entry::entryDelete($entryId);
        Entry::revisionDelete($entryId);

        return true;
    }

    public function post()
    {
        $this->Post->reset(true);
        $entryId = intval($this->Post->get('eid', EID));
        $entryBlogId = ACMS_RAM::entryBlog($entryId);
        if (roleAvailableUser()) {
            $this->Post->setMethod('entry', 'operable', (1
                && $entryId > 0
                && $entryBlogId > 0
                && roleAuthorization('entry_delete', $entryBlogId, $entryId)
            ));
        } else {
            $this->Post->setMethod('entry', 'operable', (1
                && $entryId > 0
                && $entryBlogId > 0
                && ACMS_RAM::blogLeft(SBID) <= ACMS_RAM::blogLeft($entryBlogId)
                && ACMS_RAM::blogRight(SBID) >= ACMS_RAM::blogRight($entryBlogId)
                && (0
                    || sessionWithCompilation()
                    || (1
                        && sessionWithContribution()
                        && SUID === ACMS_RAM::entryUser($entryId)
                    )
                )
            ));
        }
        $this->Post->validate();

        if ($this->Post->isValidAll()) {
            if (HOOK_ENABLE) {
                Webhook::call(BID, 'entry', 'entry:deleted', [$entryId, null]);
            }
            $entryTitle = ACMS_RAM::entryTitle($entryId);
            $this->delete($entryId);
            $redirect = $this->Post->get('redirect');

            AcmsLogger::info('「' . $entryTitle . '」エントリーを削除しました', [
                'entryId' => $entryId
            ]);

            if (!empty($redirect) && Common::isSafeUrl($redirect)) {
                $this->redirect($redirect);
            } else {
                $this->redirect(acmsLink([
                    'bid'   => BID,
                    'cid'   => CID,
                    'eid'   => '',
                ]));
            }
        }
        return $this->Post;
    }
}
