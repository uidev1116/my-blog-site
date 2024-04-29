<?php

class ACMS_POST_Entry_Mail extends ACMS_POST_Entry
{
    public $isCacheDelete  = false;

    function post()
    {
        @set_time_limit(0);

        $subject        = '';
        $plain          = '';
        $html           = '';

        if (
            1
            and ACMS_SID
            and sessionWithAdministration()
            and $bid = $this->Post->get('bid')
            and isBlogAncestor($bid, SBID, true)
            and $eid = $this->Post->get('eid')
            and findTemplate(config('mail_entry_tpl_subject'))
            and findTemplate(config('mail_entry_tpl_body_plain'))
        ) {
        } else {
            return $this->Post;
        }

        //-----------------------------------
        // セッションをクローズ（デッドロック対応）
        $phpSession = Session::handle();
        $phpSession->writeClose();

        $user_bid = ($this->Post->get('user_blog_id') > 0 ) ? $this->Post->get('user_blog_id') : $bid;

        //---------
        // subject
        $header = [
            'User-Agent: acms',
            'Accept-Language: ' . HTTP_ACCEPT_LANGUAGE,
        ];
        $url = [
            'bid' => $bid,
            'eid' => $eid,
            'tpl' => config('mail_entry_tpl_subject'),
        ];

        if (ACMS_SID) {
            $header[] = 'Cookie: ' . SESSION_NAME . '=' . ACMS_SID;
        }
        try {
            $req = Http::init(acmsLink($url), 'GET');
            $req->setRequestHeaders($header);
            $response = $req->send();
            $responseHeaders = $response->getResponseHeader();
            if (strpos(Http::getResponseHeader('http_code'), '200') === false) {
                throw new \RuntimeException(Http::getResponseHeader('http_code'));
            }
            $body = $response->getResponseBody();
            if (
                1
                and isset($responseHeaders['content-type'])
                and preg_match('@^text/[^;]+; charset=(.*)$@', $responseHeaders['content-type'], $match)
            ) {
                $subject = mb_convert_encoding($body, 'UTF-8', $match[1]);
            }
        } catch (\Exception $e) {
            AcmsLogger::warning('メールテンプレートの取得に失敗しました', Common::exceptionArray($e, ['url' => acmsLink($url)]));
            $this->addError($e->getMessage());
        }

        if (empty($subject)) {
            return $this->Post;
        } elseif (!LICENSE_PLUGIN_MAILMAGAZINE) {
            $subject = '[test]' . $subject;
        }

        //-------
        // plain
        $header = [
            'User-Agent: acms',
            'Accept-Language: ' . HTTP_ACCEPT_LANGUAGE,
        ];
        $url = [
            'bid' => $bid,
            'eid' => $eid,
            'tpl' => config('mail_entry_tpl_body_plain'),
        ];

        if (ACMS_SID) {
            $header[] = 'Cookie: ' . SESSION_NAME . '=' . ACMS_SID;
        }
        try {
            $req = Http::init(acmsLink($url), 'GET');
            $req->setRequestHeaders($header);
            $response = $req->send();
            if (strpos(Http::getResponseHeader('http_code'), '200') === false) {
                throw new \RuntimeException(Http::getResponseHeader('http_code'));
            }
            $responseHeaders = $response->getResponseHeader();
            $body = $response->getResponseBody();
            if (
                1
                and isset($responseHeaders['content-type'])
                and preg_match('@^text/plain; charset=(.*)$@', $responseHeaders['content-type'], $match)
            ) {
                $plain = mb_convert_encoding($body, 'UTF-8', $match[1]);
            }
        } catch (\Exception $e) {
            AcmsLogger::warning('メールテンプレートの取得に失敗しました', Common::exceptionArray($e, ['url' => acmsLink($url)]));
            $this->addError($e->getMessage());
        }
        if (empty($plain)) {
            return $this->Post;
        }

        //------
        // html
        $header = [
            'User-Agent: acms',
            'Accept-Language: ' . HTTP_ACCEPT_LANGUAGE,
        ];
        $url = [
            'bid' => $bid,
            'eid' => $eid,
            'tpl' => config('mail_entry_tpl_body_html'),
        ];

        if (ACMS_SID) {
            $header[] = 'Cookie: ' . SESSION_NAME . '=' . ACMS_SID;
        }
        try {
            $req = Http::init(acmsLink($url), 'GET');
            $req->setRequestHeaders($header);
            $response = $req->send();
            if (strpos(Http::getResponseHeader('http_code'), '200') === false) {
                throw new \RuntimeException(Http::getResponseHeader('http_code'));
            }
            $responseHeaders = $response->getResponseHeader();
            $body = $response->getResponseBody();
            if (
                1
                and isset($responseHeaders['content-type'])
                and preg_match('@^text/html; charset=(.*)$@', $responseHeaders['content-type'], $match)
            ) {
                $htmlCharset = $match[1];
                $html = mb_convert_encoding($body, 'UTF-8', $htmlCharset);
            }
        } catch (\Exception $e) {
            AcmsLogger::warning('メールテンプレートの取得に失敗しました', Common::exceptionArray($e, ['url' => acmsLink($url)]));
            $this->addError($e->getMessage());
        }

        //------
        // mail
        foreach (
            [
                [
                    'mail'      => 'user_mail',
                    'magazine'  => 'user_mail_magazine',
                    'html'      => true,
                ],
                [
                    'mail'      => 'user_mail_mobile',
                    'magazine'  => 'user_mail_mobile_magazine',
                    'html'      => false,
                ],
            ] as $config
        ) {
            $aryaryBcc = [];
            if ($this->Post->get('issue') and LICENSE_PLUGIN_MAILMAGAZINE) {
                $DB = DB::singleton(dsn());
                $SQL = SQL::newSelect('user');
                $SQL->setSelect($config['mail']);
                $SQL->addWhereOpr($config['magazine'], 'on');

                // 読者以外または読者で本登録済み（user_pass != ''）であること
                $shouldRegistered = SQL::newWhere();
                $shouldRegistered->addWhereOpr('user_auth', 'subscriber', '!=', 'OR');
                $shouldRegistered->addWhereOpr('user_pass', '', '!=', 'OR');

                $SQL->addWhere($shouldRegistered);
                $SQL->addWhereOpr('user_blog_id', $user_bid);
                $q  = $SQL->get(dsn());

                $n  = 0;
                $m  = 0;
                foreach ($DB->query($q, 'all') as $row) {
                    if (empty($aryaryBcc[$n])) {
                        $aryaryBcc[$n]  = [];
                    }
                    if ($row[$config['mail']]) {
                        $aryaryBcc[$n][$m]  = $row[$config['mail']];
                        $m++;
                    }
                    if ($m >= (config('mail_entry_bcc_limit') - 1)) {
                        $n++;
                        $m  = 0;
                    }
                }
            } else {
                $aryaryBcc[]    = [];
            }

            foreach ($aryaryBcc as $aryBcc) {
                $to = implode(', ', configArray('mail_entry_to'));
                $from = config('mail_entry_from');

                if (empty($to)) {
                    $to = ACMS_RAM::userMail(SUID);
                }

                try {
                    $mailer = Mailer::init();
                    $mailer = $mailer->setFrom($from)
                        ->setTo($to)
                        ->setSubject($subject)
                        ->setBody($plain);

                    if ($config['html'] && !empty($html)) {
                        $mailer = $mailer->setHtml($html);
                    }

                    if (!empty($aryBcc)) {
                        $mailer->setBcc(implode(',', $aryBcc));
                    }
                    $mailer->send();

                    AcmsLogger::info('「' . ACMS_RAM::entryTitle($eid) . '」エントリーのメールマガジンを送信しました', [
                        'to' => $to,
                        'from' => $from,
                        'subject' => $subject,
                        'bcc' => $aryBcc,
                    ]);
                } catch (Exception $e) {
                    AcmsLogger::warning('メールマガジンの送信に失敗しました', Common::exceptionArray($e, ['entryTitle' => ACMS_RAM::entryTitle($eid)]));
                }
            }
        }

        return $this->Post;
    }
}
