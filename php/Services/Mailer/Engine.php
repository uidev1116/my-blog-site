<?php

namespace Acms\Services\Mailer;

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;
use Storage;
use Common;
use RuntimeException;
use Acms\Services\Mailer\Contracts\MailerInterface;

class Engine implements MailerInterface
{
    /**
     * @var \Symfony\Component\Mailer\Mailer
     */
    protected $mailer;

    /**
     * @var \Symfony\Component\Mime\Email
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

        if (!empty($config['smtp-host'])) {
            // smtp
            $host = $config['smtp-host'];
            $port = empty($config['smtp-port']) ? '25' : $config['smtp-port'];
            $user = urlencode($config['smtp-user']);
            $passwd = urlencode($config['smtp-pass']);

            $transport = Transport::fromDsn("smtp://$user:$passwd@$host:$port");
        } else if (!empty($config['sendmail_path'])) {
            // sendmail
            $transport = Transport::fromDsn('native://default');
        }
        if (!empty($config['mail_from'])) {
            $this->returnPath = $config['mail_from'];
        }
        if (!$transport) {
            throw new RuntimeException('Failed to initialize mailer.');
        }
        $this->setMailer(new Mailer($transport));

        return $this;
    }

    /**
     * @param \Symfony\Component\Mailer\Mailer $mailer
     *
     * @return void
     */
    public function setMailer(Mailer $mailer)
    {
        $this->mailer = $mailer;
        $this->message = new Email();
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
        $this->message->subject($subject);

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
        $this->message->text($body);

        return $this;
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
        if (!empty($plain)) {
            $this->setBody($plain);
        }
        $this->message->html($html);

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
        if (!Storage::exists($path)) {
            throw new RuntimeException('Not found the attach file.');
        }
        if (!empty($filename)) {
            $this->message->attachFromPath($path, $filename, mime_content_type($path));
        } else {
            $this->message->attachFromPath($path);
        }
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

        foreach ($ary as $email) {
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

        foreach ($ary as $email) {
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

        foreach ($ary as $email) {
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
        $this->from = $from;

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
        $this->replyTo = $reply_to;

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
        if (empty($this->to)) {
            throw  new RuntimeException('\'to\' fields is empty.');
        }
        if (empty($this->from)) {
            throw  new RuntimeException('\'from\' fields is empty.');
        }
        $this->message
            ->from($this->from)
            ->to(...$this->to)
            ->priority(Email::PRIORITY_HIGH);

        if (!empty($this->cc)) {
            $this->message->cc(...$this->cc);
        }
        if (!empty($this->bcc)) {
            $this->message->bcc(...$this->bcc);
        }
        if (!empty($this->replyTo)) {
            $this->message->replyTo($this->replyTo);
        }
        if (!empty($this->returnPath)) {
            $this->message->returnPath($this->returnPath);
        }
        $this->mailer->send($this->message);
        foreach ($this->attachedFiles as $path) {
            Storage::remove($path);
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
        if ($this->message) {
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

        if (empty($txt)) {
            return array();
        }
        $emails = preg_split('/,/', $txt);

        array_walk($emails, function (&$value) {
            $value = trim($value);
            $value = str_replace(':acms-delimiter:', ',', $value);

            if (preg_match('/^("?[^\"]+"?\s+)?<?([^>]+)>?$/', $value, $matches)) {
                $email = $matches[2];
                $label = trim($matches[1], " \t\n\r\0\x0B\"");
                if (empty($label)) {
                    $value = array($email);
                } else {
                    $value = array($email => $label);
                }
            }
        });

        $emails = array_filter($emails, function ($email) {
            if (empty($email)) {
                return false;
            }
            return true;
        });

        return $emails;
    }
}
