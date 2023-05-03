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
     * @param int $revisionId
     * @return array
     */
    public function entryHook($events, $eid, $revisionId)
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
     * @param object $mail
     * @param object $mailAdmin
     * @param object $field
     * @return array
     */
    public function formHook($events, $mail, $mailAdmin, $field)
    {
        $contents = array(
            'mail' => $mail,
            'mailAdmin' => $mailAdmin,
            'field' => $field,
        );
        return $this->basicPayload('form', $events, $contents, REQUEST_URL);
    }
}
