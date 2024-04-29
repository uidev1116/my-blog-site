<?php

namespace Acms\Services\Webhook\Contracts;

use ACMS_RAM;

abstract class Payload
{
    protected function basicPayload($type, $events, $contents, $url = null)
    {
        $payload = [
            'webhook_id' => 0,
            'webhook_name' => '',
            'type' => $type,
            'event' => implode(',', $events),
            'actor' => null,
            'url' => $url,
            'contents' => $contents,
        ];
        if (SUID) {
            $payload['actor'] = [
                'uid' => SUID,
                'name' => ACMS_RAM::userName(SUID),
            ];
        }
        return $payload;
    }
}
