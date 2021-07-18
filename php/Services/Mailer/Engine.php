<?php

namespace Acms\Services\Mailer;

use Storage;
use Common;
use Swift;
use Swift_Mailer;
use Swift_Message;
use Swift_Attachment;
use Swift_SmtpTransport;
use Swift_SendmailTransport;
use RuntimeException;
use Acms\Services\Mailer\Contracts\MailerInterface;
use Acms\Services\Facades\Process;

class Engine implements MailerInterface
{
    /**
     * @var \Swift_Mailer
     */
    protected $mailer;

    /**
     * @var \Swift_Message
     */
    protected $message;

    /**
     * @var string
     */
    protected $from;

    /**
     * @var string
     */
    protected $replyTo;

    /**
     * @var string
     */
    protected $returnPath;

    /**
     * @var array
     */
    protected $to = array();

    /**
     * @var array
     */
    protected $cc = array();

    /**
     * @var array
     */
    protected $bcc = array();

    /**
     * @var array
     */
    protected $attachedFiles = array();

    /**
     * @var bool
     */
    protected static $isIso2022Jp = false;

    /**
     * Mailer Engine constructor.
     */
    public function __construct()
    {

    }

    /**
     * 初期化
     *
     * @return self
     */
    public function init()
    {
        $transport = null;
        $config = Common::mailConfig();

        $this->allowRfcViolation(); // RFC違反メールアドレスの許可

        if ( !empty($config['smtp-host']) ) {
            // smtp
            $host = $config['smtp-host'];
            $port = empty($config['smtp-port']) ? '25' : $config['smtp-port'];
            $user = $config['smtp-user'];
            $passwd = $config['smtp-pass'];

            $transport = Swift_SmtpTransport::newInstance($host, $port);
            switch ( config('mail_smtp-use-ssl') ) {
                case 'ssl':
                    $transport->setEncryption('ssl');
                    break;
                case 'tls':
                    $transport->setEncryption('tls');
                    break;
                case 'starttls';
                    $transport->setEncryption('tls');
                    $transport->setStreamOptions(array(
                        'ssl' => array(
                            'allow_self_signed' => true,
                            'verify_peer' => false
                        )
                    ));
                    break;
                default:
                    break;
            }
            switch ( config('mail_smtp-auth-mode') ) {
                case 'plain':
                    $transport->setAuthMode('plain');
                    break;
                case 'login':
                    $transport->setAuthMode('login');
                    break;
                case 'cram-md5':
                    $transport->setAuthMode('cram-md5');
                    break;
                default:
                    break;
            }
            if ( !empty($user) ) {
                $transport->setUsername($user);
            }
            if ( !empty($passwd) ) {
                $transport->setPassword($passwd);
            }

        } else if ( !empty($config['sendmail_path']) ) {
            // sendmail
            $path = $config['sendmail_path'];
            $transport = Swift_SendmailTransport::newInstance($path);

        }
        if ( !empty($config['mail_from']) ) {
            $this->returnPath = $config['mail_from'];
        }
        if ( !$transport ) {
            throw new RuntimeException('Failed to initialize mailer.');
        }
        $this->setMailer(Swift_Mailer::newInstance($transport));

        return $this;
    }

    /**
     * @param \Swift_Mailer $mailer
     *
     * @return void
     */
    public function setMailer(Swift_Mailer $mailer)
    {
        $this->mailer = $mailer;
        $this->message = Swift_Message::newInstance();
    }

    /**
     * iso-2022-jpで送信するように設定（デフォルトUTF-8）
     *
     * @return self
     */
    public function setEncoderIso2022Jp()
    {
        Swift::init(
            function () {
                // 日本語に関する初期設定 (ISO 2022)
                // @link http://qiita.com/inouet/items/900a7241ab543f7d6ea7
                \Swift_DependencyContainer::getInstance()
                    ->register('mime.qpheaderencoder')
                    ->asAliasOf('mime.base64headerencoder');
                \Swift_Preferences::getInstance()->setCharset('iso-2022-jp');
            }
        );
        self::$isIso2022Jp = true;

        return $this;
    }

    /**
     * @return self
     */
    public function getInstance()
    {
        return $this;
    }

    /**
     * Subjectを設定
     *
     * @param string $subject
     *
     * @return self
     */
    public function setSubject($subject)
    {
        $this->message->setSubject($subject);

        return $this;
    }

    /**
     * 本文を設定
     *
     * @param string $body
     *
     * @return self
     */
    public function setBody($body)
    {
        if ( self::$isIso2022Jp ) {
            $body = $this->charReplace($body, 'iso-2022-jp');
        }
        $this->body = $body;
        $this->message->setBody($body, 'text/plain');

        return $this;
    }

    public function charReplace($contents, $to)
    {
        $charset = strtolower($to);
        $path = SCRIPT_DIR . config('const_mail_convert_dir') . $charset . '.php';

        if ( Storage::exists($path) ) {
            $const  = array();
            include $path;
            $contents   = str_replace(array_keys($const), array_values($const), $contents);
        }
        return $contents;
    }

    /**
     * HTML（本文）を設定
     *
     * @param string $html
     * @param string $plain
     *
     * @return $this
     */
    public function setHtml($html, $plain = null)
    {
        $this->html = $html;
        if ( !empty($plain) ) {
            $this->setBody($plain);
        }
        $this->message->addPart($html, 'text/html');

        return $this;
    }

    /**
     * 添付ファイルの追加
     *
     * @param string $path
     * @param string $filename
     *
     * @return self
     *
     * @throws \RuntimeException
     */
    public function attach($path, $filename = '')
    {
        if ( !Storage::exists($path) ) {
            throw new RuntimeException('Not found the attach file.');
        }
        $attachment = Swift_Attachment::fromPath($path);

        if ( !empty($filename) ) {
            $attachment->setFilename($filename)
                ->setContentType(mime_content_type($path));
        }
        $this->message->attach($attachment);
        $this->attachedFiles[] = $path;

        return $this;
    }

    /**
     * Toを設定
     *
     * @param array $to
     *
     * @return self
     */
    public function setTo($to)
    {
        return $this->addTo($to);
    }

    /**
     * Toを追加
     *
     * @param string $to
     *
     * @return self
     */
    public function addTo($to)
    {
        $ary = $this->parseAddress($to);

        foreach ( $ary as $email ) {
            $email = is_array($email) ? $email : array($email);
            $this->to = array_merge($this->to, $email);
        }

        return $this;
    }

    /**
     * Ccを設定
     *
     * @param string $cc
     *
     * @return self
     */
    public function setCc($cc)
    {
        return $this->addCc($cc);
    }

    /**
     * Ccを追加
     *
     * @param string $cc
     *
     * @return self
     */
    public function addCc($cc)
    {
        $ary = $this->parseAddress($cc);

        foreach ( $ary as $email ) {
            $email = is_array($email) ? $email : array($email);
            $this->cc = array_merge($this->cc, $email);
        }

        return $this;
    }

    /**
     * Bccを設定
     *
     * @param string $bcc
     *
     * @return self
     */
    public function setBcc($bcc)
    {
        return $this->addBcc($bcc);
    }

    /**
     * Bccを追加
     *
     * @param string $bcc
     *
     * @return self
     */
    public function addBcc($bcc)
    {
        $ary = $this->parseAddress($bcc);

        foreach ( $ary as $email ) {
            $email = is_array($email) ? $email : array($email);
            $this->bcc = array_merge($this->bcc, $email);
        }

        return $this;
    }

    /**
     * Fromを設定
     *
     * @param string $from
     *
     * @return self
     */
    public function setFrom($from)
    {
        $ary = $this->parseAddress($from);
        if ( isset($ary[0]) && !empty($ary[0]) ) {
            $this->from = $ary[0];
        }

        return $this;
    }

    /**
     * ReplyToを設定
     *
     * @param string $reply_to
     *
     * @return self
     */
    public function setReplyTo($reply_to)
    {
        $ary = $this->parseAddress($reply_to);
        if ( isset($ary[0]) && !empty($ary[0]) ) {
            $this->replyTo = $ary[0];
        }

        return $this;
    }

    /**
     * 送信
     *
     * @param $background
     * @return self
     *
     * @throws \RuntimeException
     */
    public function send($background = 'default')
    {
        if ($background === 'default') {
            $background = config('send_email_asynchronous') === 'on';
        }
        if ( empty($this->to) ) {
            throw  new RuntimeException('\'to\' fields is empty.');
        }
        if ( empty($this->from) ) {
            throw  new RuntimeException('\'from\' fields is empty.');
        }
        $this->message
            ->setFrom($this->from)
            ->setTo($this->to);

        if ( !empty($this->cc) ) {
            $this->message->setCc($this->cc);
        }
        if ( !empty($this->bcc) ) {
            $this->message->setBcc($this->bcc);
        }
        if ( !empty($this->replyTo) ) {
            $this->message->setReplyTo($this->replyTo);
        }
        if ( !empty($this->returnPath) ) {
            $this->message->setReturnPath($this->returnPath);
        }
        if ( self::$isIso2022Jp ) {
            $this->message->setCharset('iso-2022-jp')
                ->setEncoder(new \Swift_Mime_ContentEncoder_PlainContentEncoder('7bit'))
                ->setMaxLineLength(0);
        }
        if ( $background === true ) {
            $manager = Process::newProcessManager();
            $mailer = $this->mailer;
            $message = $this->message;
            $attachedFiles = $this->attachedFiles;
            $manager->addTask(function () use ($mailer, $message, $attachedFiles) {
                $result = $mailer->send($message);
                foreach ($attachedFiles as $path) {
                    Storage::remove($path);
                }
                if ( $result < 1 ) {
                    throw new RuntimeException('Failed to send messages.');
                }
            });
            $manager->run();
        } else {
            $result = $this->mailer->send($this->message);
            foreach ($this->attachedFiles as $path) {
                Storage::remove($path);
            }
            if ( $result < 1 ) {
                throw new RuntimeException('Failed to send messages.');
            }
        }
        return $this;
    }

    /**
     * メールをstringで取得
     *
     * @return string
     */
    public function getMessage()
    {
        if ( $this->message ) {
            return $this->message->toString();
        }

        return '';
    }

    /**
     * アドレス指定を分割
     *
     * @param string $txt
     *
     * @return array
     */
    public function parseAddress($txt)
    {
        $txt = preg_replace_callback('/"[^"]*"/', function ($matches) {
            return str_replace(',', ':acms-delimiter:', $matches[0]);
        }, $txt);

        if ( empty($txt) ) {
            return array();
        }
        $emails = preg_split('/,/', $txt);

        array_walk($emails, function (& $value) {
            $value = trim($value);
            $value = str_replace(':acms-delimiter:', ',', $value);

            if ( preg_match('/^("?[^\"]+"?\s+)?<?([^>]+)>?$/', $value, $matches) ) {
                $email = $matches[2];
                $label = trim($matches[1], " \t\n\r\0\x0B\"");
                if ( empty($label) ) {
                    $value = array($email);
                } else {
                    $value = array($email => $label);
                }
            }
        });

        $emails = array_filter($emails, function($email) {
            if ( empty($email) ) {
                return false;
            }
            return true;
        });

        return $emails;
    }

    /**
     * RFC違反のメールアドレスの許可
     *
     * @return void
     */
    protected function allowRfcViolation()
    {
        \Swift_DependencyContainer::getInstance()
            ->register('mime.grammar')
            ->asSharedInstanceOf('Acms\Services\Mailer\SwiftGrammar');
    }
}
