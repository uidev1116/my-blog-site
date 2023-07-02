<?php

class ACMS_GET_Admin_Webhook_EventList extends ACMS_GET
{
    function get()
    {
        $webhookType = $this->Get->get('type');
        $json = array();
        $webhookEventValue = configArray("webhook_event_{$webhookType}");
        $webhookEventLabel = configArray("webhook_event_{$webhookType}_label");

        if (0
            || empty($webhookEventValue)
            || empty($webhookEventLabel)
            || count($webhookEventValue) !== count($webhookEventLabel))
        {
            Common::responseJson($json);
        }
        foreach ($webhookEventValue as $i => $value) {
            $json[] = array(
                'value' => $value,
                'label' => $webhookEventLabel[$i],
            );
        }
        Common::responseJson($json);
    }
}
