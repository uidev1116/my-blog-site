<?php

class ACMS_POST_PingWeblogUpdate extends ACMS_POST
{
    var $isCacheDelete  = false;

    function post()
    {
        if ( !sessionWithCompilation() ) die();
        if ( !IS_LICENSED ) die();
        $Ping = $this->extract('pingUpdate');

        try {
            $tpl = Storage::get(THEMES_DIR.'system'.$Ping->get('pingUpdateTpl'));
        } catch ( \Exception $e ) {
            return false;
        }
        $tpl = setGlobalVars($tpl);



        $siteName   = ACMS_RAM::blogName(BID);
        $siteUrl    = acmsLink(array('bid' => BID, 'protocol' => 'http'), false);
        $checkLink  = acmsLink(array('bid' => BID, 'cid' => CID, 'eid' => EID, 'protocol' => 'http'), false);

        //------
        // ping
        if ( $aryEndpoint = $Ping->getArray('ping_weblog_updates_endpoint') ) {
            $Tpl = new Template($tpl);
            $Tpl->add(null, array(
                'method'    => 'ping',
                'siteName'  => $siteName,
                'siteLink'  => $siteUrl,
            ));
            $xml = $Tpl->get();

            foreach ( $aryEndpoint as $endpoint ) {
                try {
                    $req = \Http::init($endpoint, 'post');
                    $req->setRequestHeaders(array(
                        'Content-Type: text/xml',
                        'User-Agent: a-blog cms'
                    ));
                    $req->setPostData($xml);
                    $response = $req->send();
                    $response->getResponseBody();
                } catch (\Exception $e) {}
            }
        }

        //--------------
        // extendedPing
        if ( $aryEndpoint = $Ping->getArray('ping_weblog_updates_extended_endpoint') ) {
            $Tpl = new Template($tpl);
            if ( CID ) {
                $Tpl->add('cid');
                $Tpl->add('category');
            }
            $Tpl->add(null, array(
                'method'    => 'extendedPing',
                'siteName'  => $siteName,
                'siteLink'  => $siteUrl,
                'checkLink' => $checkLink,
            ));
            $xml = $Tpl->get();

            foreach ( $aryEndpoint as $endpoint ) {
                try {
                    $req = \Http::init($endpoint, 'post');
                    $req->setRequestHeaders(array(
                        'Content-Type: text/xml',
                        'User-Agent: a-blog cms'
                    ));
                    $req->setPostData($xml);
                    $response = $req->send();
                    $response->getResponseBody();
                } catch (\Exception $e) {}
            }
        }

        return $this->Post;
    }
}
