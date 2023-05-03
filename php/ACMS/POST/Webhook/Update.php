<?php

class ACMS_POST_Webhook_Update extends ACMS_POST
{
    function post()
    {
        $input = $this->extract('webhook', new ACMS_Validator());
        $id = $this->Get->get('id', null);

        if ('global' !== $input->get('scope')) {
            $input->set('scope', 'local');
        }
        if ('on' !== $input->get('request-history')) {
            $input->set('request-history', 'off');
        }
        if ('custom' !== $input->get('payload')) {
            $input->set('payload', 'default');
        }

        $sql = SQL::newSelect('webhook');
        $sql->addSelect('webhook_blog_id');
        $sql->addWhereOpr('webhook_id', $id);
        $bid = DB::query($sql->get(dsn()), 'one');

        $input->setMethod('status', 'in', array('open', 'close'));
        $input->setMethod('name', 'required');
        $input->setMethod('type', 'required');
        $input->setMethod('events', 'required');
        $input->setMethod('url', 'required');
        $input->setMethod('webhook', 'operative', sessionWithAdministration($bid));
        $input->validate(new ACMS_Validator());

        if ($this->Post->isValidAll()) {
            $sql = SQL::newUpdate('webhook');
            $sql->addUpdate('webhook_status', $input->get('status'));
            $sql->addUpdate('webhook_name', $input->get('name'));
            $sql->addUpdate('webhook_type', $input->get('type'));
            $sql->addUpdate('webhook_events', $input->get('events'));
            $sql->addUpdate('webhook_url', $input->get('url'));
            $sql->addUpdate('webhook_history', $input->get('history'));
            $sql->addUpdate('webhook_scope', $input->get('scope'));
            $sql->addUpdate('webhook_payload', $input->get('payload'));
            $sql->addUpdate('webhook_payload_tpl', $input->get('payload_tpl'));
            $sql->addUpdate('webhook_secret', $input->get('secret'));
            $sql->addUpdate('webhook_blog_id', BID);
            $sql->addWhereOpr('webhook_id', $id);
            DB::query($sql->get(dsn()), 'exec');

            $this->addMessage('Webhookを保存しました');
        } else {
            $this->addError('Webhookの保存に失敗しました');
        }
        return $this->Post;
    }
}
