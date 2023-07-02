<?php

class ACMS_POST_Trackback_Receive extends ACMS_POST
{
    function post()
    {
        $tpl    = <<< EOF
<?xml version="1.0" encoding="UTF-8"?>
<response>
	<error>{error}</error><!-- BEGIN msg -->
	<message>{msg}</message><!-- END msg --><!-- BEGIN extended -->
	<title>{title}</title>
	<excerpt>{excerpt}</excerpt>
	<url>{url}</url>
	<blog_name>{blogName}</blog_name><!-- END extended -->
</response>
EOF;
        $Tpl    = new Template($tpl);

        $this->Post->setMethod('trackback', 'disabled', ('on' == config('trackback')));
        $this->Post->setMethod('entry', 'eidIsNull', !!EID);
        foreach ( array('title', 'excerpt', 'url', 'blog_name') as $key ) {
            $this->Post->setMethod($key, 'required');
        }
        $this->Post->validate(new ACMS_Validator());
        if ( !$this->Post->isValidAll() ) {
            $aryKey = array();
            foreach ( array('title', 'excerpt', 'url', 'blog_name') as $key ) {
                if ( $this->Post->isValid($key) ) continue;
                $aryKey[]   = $key;
            }

            $msg    = '';
            if ( !empty($aryKey) ) $msg = join(', ', $aryKey).' is required.';
            if ( !$this->Post->isValid('entry') ) $msg = 'requested url is not trackback endpoint.';
            if ( !$this->Post->isValid('trackback') ) $msg = 'trackback is not available now.';

            header('Content-type: application/xml; charset=UTF-8');
            $Tpl->add(null, array(
                'error' => '1',
                'msg'   => $msg,
            ));
            die($Tpl->get());
        }

        //------------
        // byte check
        if ( 'on' == config('trackback_byte_check') ) {
            $txt    = '';
            foreach ( array('title', 'excerpt', 'url', 'blog_name') as $key ) {
                $txt    .= $this->Post->get($key);
            }
            if ( strlen($txt) == mb_strlen($txt) ) {
                header('Content-type: application/xml; charset=UTF-8');
                $Tpl->add(null, array(
                    'error' => '1',
                    'msg'   => 'your request is refused.',
                ));
                die($Tpl->get());
            }
        }


        //------------
        // link check
        if ( 'on' == config('trackback_link_check') ) {
            if ( !is_int(strpos(strval(Storage::get($this->Post->get('url'))), acmsLink(array('bid' => BID), false))) ) {
                header('Content-type: application/xml; charset=UTF-8');
                $Tpl->add(null, array(
                    'error' => '1',
                    'msg'   => 'your request is refused.',
                ));
                die($Tpl->get());
            }
        }


        //----
        // db
        $DB     = DB::singleton(dsn());
        $tbid   = $DB->query(SQL::nextval('trackback_id', dsn()), 'seq');
        $SQL    = SQL::newInsert('trackback');
        $SQL->addInsert('trackback_id', $tbid);
        $SQL->addInsert('trackback_status', config('trackback_status'));
        $SQL->addInsert('trackback_host', REMOTE_ADDR);
        $SQL->addInsert('trackback_datetime', date('Y-m-d H:i:s', REQUEST_TIME));
        $SQL->addInsert('trackback_flow', 'receive');
        $SQL->addInsert('trackback_entry_id', EID);
        $SQL->addInsert('trackback_blog_id', BID);
        foreach ( array('title', 'excerpt', 'url', 'blog_name') as $key ) {
            $SQL->addInsert('trackback_'.$key, 
                mb_strimwidth($this->Post->get($key), 0, 252, '...', 'UTF-8')
            );
        }
        $DB->query($SQL->get(dsn()), 'exec');


        //------
        // mail
        $this->Post->set('trackbackUrl', acmsLink(array(
            'bid'       => BID,
            'eid'       => EID,
            'tbid'      => $tbid,
            'fragment'  => 'trackback-'.$tbid,
        ), false));

        $isSend = false;
        if ( 1
            and $to = configArray('mail_trackback_to')
            and $subjectTpl = findTemplate(config('mail_trackback_tpl_subject'))
            and $bodyTpl    = findTemplate(config('mail_trackback_tpl_body'))
        ) {
            $subject    = Common::getMailTxt($subjectTpl, $this->Post);
            $body       = Common::getMailTxt($bodyTpl, $this->Post);

            $to = implode(', ', $to);
            $from = config('mail_trackback_from');
            $bcc = implode(', ', configArray('mail_trackback_bcc'));

            try {
                $mailer = Mailer::getInstance();
                $mailer->setFrom($from)
                    ->setTo($to)
                    ->setBcc($bcc)
                    ->setSubject($subject)
                    ->setBody($body)
                    ->send();
            } catch ( Exception $e  ) {}
        }


        header('Content-type: application/xml; charset=UTF-8');
        $Tpl->add(null, array(
            'error' => '0',
        ));
        die($Tpl->get());
    }
}
