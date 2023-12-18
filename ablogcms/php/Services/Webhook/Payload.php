<?php

namespace Acms\Services\Webhook;

use Acms\Services\Webhook\Contracts\Payload as PayloadContract;
use DB;
use SQL;

class Payload extends PayloadContract
{
    /**
     * @param array $events
     * @param int $eid
     * @param int|null $revisionId
     * @return array
     */
    public function entryHook(array $events, int $eid, ?int $revisionId): array
    {
        if (empty($revisionId) || $revisionId < 2) {
            $revisionId = null;
        }
        if (is_integer($revisionId) && $revisionId > 1) {
            $sql = SQL::newSelect('entry_rev');
            $sql->addWhereOpr('entry_rev_id', $revisionId);
        } else {
            $sql = SQL::newSelect('entry');
        }
        $sql->addWhereOpr('entry_id', $eid);
        $entry = DB::query($sql->get(dsn()), 'row');
        $entryData = array();
        foreach ($entry as $key => $value) {
            $entryData[substr($key, strlen('entry_'))] = $value;
        }
        $contents = array(
            'entry' => $entryData,
            'field' => loadEntryField($eid, $revisionId)->_aryField,
        );
        $url = acmsLink(array(
            'bid' => $entryData['blog_id'],
            'cid' => $entryData['category_id'],
            'eid' => $eid,
        ), false);
        return $this->basicPayload('entry', $events, $contents, $url);
    }


    /**
     * @param array $events
     * @param array $mail
     * @param array $mailAdmin
     * @param array $field
     * @return array
     */
    public function formHook(array $events, array $mail, array $mailAdmin, array $field): array
    {
        $contents = array(
            'mail' => $mail,
            'mailAdmin' => $mailAdmin,
            'field' => $field,
        );
        return $this->basicPayload('form', $events, $contents, REQUEST_URL);
    }

    /**
     * @param array $events
     * @param int $uid
     * @return array
     */
    public function userHook($events, $uid): array
    {
        $sql = SQL::newSelect('user');
        $sql->addWhereOpr('user_id', $uid);
        $user = DB::query($sql->get(dsn()), 'row');
        $userData = array();
        foreach ($user as $key => $value) {
            if (in_array($key, ['user_pass', 'user_pass_reset', 'user_tfa_secret', 'user_tfa_secret_iv', 'user_tfa_recovery', 'user_session_data'])) {
                continue;
            }
            $userData[substr($key, strlen('user_'))] = $value;
        }
        $contents = array(
            'user' => $userData,
            'field' => loadEntryField($uid)->_aryField,
        );
        return $this->basicPayload('user', $events, $contents, '');
    }
}
