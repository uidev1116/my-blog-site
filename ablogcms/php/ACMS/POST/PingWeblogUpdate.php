<?php

use Acms\Services\Facades\Storage;
use Acms\Services\Facades\Common;
use Acms\Services\Facades\Http;
use Acms\Services\Facades\Logger;

class ACMS_POST_PingWeblogUpdate extends ACMS_POST
{
    public $isCacheDelete = false;

    public function post()
    {
        if (!sessionWithCompilation()) {
            die();
        }
        if (!IS_LICENSED) {
            die();
        }

        try {
            $tplPath = THEMES_DIR . 'system/rpc/weblog-updates-ping.xml';
            $tpl = Storage::get($tplPath);
        } catch (Exception $e) {
            Logger::notice('PINGのテンプレート取得に失敗しました', Common::exceptionArray($e, ['tpl' => $tplPath]));
            return false;
        }
        $tpl = setGlobalVars($tpl);

        $siteName = ACMS_RAM::blogName(BID);
        $siteUrl = acmsLink(['bid' => BID, 'protocol' => 'http'], false);
        $checkLink = acmsLink(['bid' => BID, 'cid' => CID, 'eid' => EID, 'protocol' => 'http'], false);

        //------
        // ping
        if ($aryEndpoint = configArray('ping_weblog_updates_endpoint')) {
            $Tpl = new Template($tpl);
            $Tpl->add(null, [
                'method' => 'ping',
                'siteName' => $siteName,
                'siteLink' => $siteUrl,
            ]);
            $xml = $Tpl->get();

            foreach ($aryEndpoint as $endpoint) {
                try {
                    $req = Http::init($endpoint, 'post');
                    $req->setRequestHeaders([
                        'Content-Type: text/xml',
                        'User-Agent: a-blog cms',
                    ]);
                    $req->setPostData($xml);
                    $response = $req->send();
                    if (strpos(Http::getResponseHeader('http_code'), '200') === false) {
                        throw new RuntimeException(Http::getResponseHeader('http_code'));
                    }
                    $response->getResponseBody();
                } catch (Exception $e) {
                    Logger::notice('Ping送信に失敗しました', Common::exceptionArray($e, ['url' => $endpoint]));
                }
            }
        }

        //--------------
        // extendedPing
        if ($aryEndpoint = configArray('ping_weblog_updates_extended_endpoint')) {
            $Tpl = new Template($tpl);
            if (CID) {
                $Tpl->add('cid');
                $Tpl->add('category');
            }
            $Tpl->add(null, [
                'method' => 'extendedPing',
                'siteName' => $siteName,
                'siteLink' => $siteUrl,
                'checkLink' => $checkLink,
            ]);
            $xml = $Tpl->get();

            foreach ($aryEndpoint as $endpoint) {
                try {
                    $req = Http::init($endpoint, 'post');
                    $req->setRequestHeaders([
                        'Content-Type: text/xml',
                        'User-Agent: a-blog cms',
                    ]);
                    $req->setPostData($xml);
                    $response = $req->send();
                    if (strpos(Http::getResponseHeader('http_code'), '200') === false) {
                        throw new RuntimeException(Http::getResponseHeader('http_code'));
                    }
                    $response->getResponseBody();
                } catch (Exception $e) {
                    Logger::notice('Ping送信に失敗しました', Common::exceptionArray($e, ['url' => $endpoint]));
                }
            }
        }

        return $this->Post;
    }
}
