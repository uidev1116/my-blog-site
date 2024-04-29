<?php

class ACMS_GET_Admin_Webhook_Edit extends ACMS_GET_Admin_Edit
{
    function auth()
    {
        if (sessionWithAdministration()) {
            return true;
        }
        return false;
    }

    function edit(&$Tpl)
    {
        $id = $this->Get->get('id');
        $data = $this->Post->getChild('webhook');

        $sql = SQL::newSelect('webhook');
        $sql->addSelect('webhook_blog_id');
        $sql->addWhereOpr('webhook_id', $id);
        $bid = DB::query($sql->get(dsn()), 'one');

        if ($id && (empty($bid) || !sessionWithAdministration($bid))) {
            $Tpl->add('error#auth');
            return true;
        }

        if ($data->isNull()) {
            $_data = loadWebhook($id);
            if (!$_data->get('status')) {
                $_data->setField('status', 'open');
            }
            $data->overload($_data);
        }

        // webhook の タイプ選択肢を組み立て
        $types = configArray('webhook_types');
        $labels = configArray('webhook_types_label');
        foreach ($types as $i => $type) {
            $item = [
                'type_value' => $type,
                'type_label' => $labels[$i],
            ];
            if ($data->get('type') === $type) {
                $item['selected'] = config('attr_selected');
            }
            $Tpl->add('type_group:loop', $item);
        }
        if ($type = $data->get('type')) {
            $webhookEventValue = configArray("webhook_event_{$type}");
            $webhookEventLabel = configArray("webhook_event_{$type}_label");
            $events = explode(',', $data->get('events'));
            $labels = [];
            foreach ($webhookEventValue as $i => $value) {
                if (in_array($value, $events, true)) {
                    $labels[] = $webhookEventLabel[$i];
                }
            }
            $data->add('events-label', implode(',', $labels));
        }

        return true;
    }
}
