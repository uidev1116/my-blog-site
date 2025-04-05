<?php

namespace Acms\Services\Facades;

/**
 * @method static \Acms\Services\Mailer\Engine init() メールエンジンを初期化
 * @method static \Acms\Services\Mailer\Engine setSubject(string $subject) メールの件名を設定
 * @method static \Acms\Services\Mailer\Engine setTo(string $to) メールの送信先を設定
 * @method static \Acms\Services\Mailer\Engine setFrom(string $from) メールの送信者を設定
 * @method static \Acms\Services\Mailer\Engine setBody(string $body) メールの本文を設定
 * @method static \Acms\Services\Mailer\Engine send(bool $removeAttachedFiles = true) メールを送信
 * @method static \Acms\Services\Mailer\Engine attach(string $path, string $filename) メールに添付ファイルを追加
 * @method static \Acms\Services\Mailer\Engine setCc(string $cc) メールのCCを設定
 * @method static \Acms\Services\Mailer\Engine addCc(string $cc) メールのCCを追加
 * @method static \Acms\Services\Mailer\Engine setBcc(string $bcc) メールのBCCを設定
 * @method static \Acms\Services\Mailer\Engine addBcc(string $bcc) メールのBCCを追加
 * @method static \Acms\Services\Mailer\Engine setReplyTo(string $reply_to) メールの返信先を設定
 * @method static \Acms\Services\Mailer\Engine setHtml(string $html, ?string $plain = null) HTML形式のメールを設定
 * @method static string getMessage() メールの内容を取得
 * @method static array parseAddress(string $txt) メールの送信先を解析
 * @method static void setMailer(\Symfony\Component\Mailer\Mailer $mailer) メールエンジンを設定
 * @method static \Acms\Services\Mailer\Engine getInstance() メールエンジンのインスタンスを取得
 */
class Mailer extends Facade
{
    /**
     * @return string
     */
    protected static function getServiceAlias()
    {
        return 'mailer';
    }
}
