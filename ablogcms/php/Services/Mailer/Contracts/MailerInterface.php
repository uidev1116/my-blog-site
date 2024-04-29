<?php

namespace Acms\Services\Mailer\Contracts;

interface MailerInterface
{
    /**
     * Subjectを設定
     *
     * @param string $subject
     * @return self
     */
    public function setSubject($subject);

    /**
     * Toを設定
     *
     * @param array $to
     * @return self
     */
    public function setTo($to);

    /**
     * Fromを設定
     *
     * @param string $from
     * @return self
     */
    public function setFrom($from);

    /**
     * 本文を設定
     *
     * @param string $body
     * @return self
     */
    public function setBody($body);

    /**
     * 送信
     *
     * @return void
     */
    public function send();

    /**
     * 添付ファイルの追加
     *
     * @param string $path
     * @param string $filename
     * @return self
     */
    public function attach($path, $filename);
}
