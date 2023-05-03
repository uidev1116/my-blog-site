<?php

namespace Acms\Services\Webhook;

use DB;
use SQL;
use ACMS_Filter;

class Engine
{
    protected $payload;

    /**
     * Engine constructor.
     */
    public function __construct($payload)
    {
        if (!defined('CURL_SSLVERSION_TLSv1_2')) {
            define('CURL_SSLVERSION_TLSv1_2', 6);
        }
        $this->payload = $payload;
    }

    /**
     * @param int $bid
     * @param string $type
     * @param array|string $events
     * @param array $args
     */
    public function call($bid, $type, $events, $args = array())
    {
        if (!is_array($events)) {
            $events = array($events);
        }
        $hooks = $this->getHooks($bid, $type, $events);
        if (empty($hooks)) {
            return;
        }
        $payload = $this->getPayload($type, $events, $args);

        foreach ($hooks as $hook) {
            $payload['webhook_id'] = $hook['webhook_id'];
            $payload['webhook_name'] = $hook['webhook_name'];
            $this->send($hook, $payload, $events);
        }
    }

    /**
     * @param int $bid
     * @param string $type
     * @param array $events
     * @return mixed
     */
    protected function getHooks($bid, $type, $events)
    {
        $sql = SQL::newSelect('webhook');
        $sql->addLeftJoin('blog', 'blog_id', 'webhook_blog_id');
        ACMS_Filter::blogTree($sql, $bid, 'ancestor-or-self');
        $sql->addWhereOpr('webhook_status', 'open');
        $sql->addWhereOpr('webhook_type', $type);
        $where = SQL::newWhere();
        foreach ($events as $event) {
            $where->addWhere(SQL::newFunction( "'{$event}', webhook_events", 'FIND_IN_SET'), 'OR');
        }
        $sql->addWhere($where);
        $where = SQL::newWhere();
        $where->addWhereOpr('webhook_blog_id', $bid, '=', 'OR');
        $where->addWhereOpr('webhook_scope', 'global', '=', 'OR');
        $sql->addWhere($where);
        $sql->addOrder('webhook_id', 'DESC');
        $q = $sql->get(dsn());

        return DB::query($q, 'all');
    }

    /**
     * @param string $type
     * @param array $events
     * @param array $args
     * @return false|mixed
     */
    protected function getPayload($type, $events, $args = array())
    {
        if (!is_array($args)) {
            $args = array($args);
        }
        array_unshift($args, $events);
        $methodName = "{$type}Hook";

        if (method_exists($this->payload, $methodName)) {
            return call_user_func_array(array($this->payload, $methodName), $args);
        }
        return false;
    }

    /**
     * @param array $hook
     * @param string $payload
     * @param array $events
     */
    protected function send($hook, $payload, $events)
    {
        $headers = array(
            'Content-Type: application/json',
        );
        if ($hook['webhook_payload'] === 'custom') {
            $data = $this->buildPayload($payload, $hook['webhook_payload_tpl']);
        } else {
            $data = json_encode($payload, JSON_PRETTY_PRINT);
        }

        $ch = curl_init($hook['webhook_url']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        if ($hook['webhook_history'] === 'on') {
            $this->saveLog($ch, $hook['webhook_id'], $hook['webhook_url'], $events, implode("\n", $headers), $data);
        }
        curl_close($ch);
    }

    /**
     * @param $payload
     * @param $tpl
     * @return mixed
     */
    protected function buildPayload($payload, $tpl)
    {
        $tplEngine = new Template;
        return $tplEngine->render(setGlobalVars($tpl), $payload);
    }

    /**
     * @param $curl
     * @param $id
     * @param $endpoint
     * @param $events
     * @param $requestHeader
     * @param $requestBody
     */
    protected function saveLog($curl, $id, $endpoint, $events, $requestHeader, $requestBody)
    {
        $response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $time = curl_getinfo($curl, CURLINFO_TOTAL_TIME);
        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);

        $responseHeader = substr($response, 0, $headerSize);
        $responseBody = substr($response, $headerSize);

        $sql = SQL::newInsert('log_webhook');
        $sql->addInsert('log_webhook_datetime', date('Y-m-d H:i:s', REQUEST_TIME));
        $sql->addInsert('log_webhook_endpoint', $endpoint);
        $sql->addInsert('log_webhook_status_code', $status);
        $sql->addInsert('log_webhook_event', implode(',', $events));
        $sql->addInsert('log_webhook_id', $id);
        $sql->addInsert('log_webhook_request_header', $requestHeader);
        $sql->addInsert('log_webhook_request_body', $requestBody);
        $sql->addInsert('log_webhook_response_header', $responseHeader);
        $sql->addInsert('log_webhook_response_body', $responseBody);
        $sql->addInsert('log_webhook_response_time', $time);
        DB::query($sql->get(dsn()), 'exec');
    }
}
