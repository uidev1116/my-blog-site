<?php

class ACMS_POST_Download extends ACMS_POST
{
    /**
     * @var bool
     */
    public $isCacheDelete  = false;

    /**
     * @var bool
     */
    protected $isCSRF = false;

    function post()
    {
        $Q = \Common::getUriObject($this->Post);
        $headerAry = array(
            'User-Agent: acms',
            'Accept-Language: ' . HTTP_ACCEPT_LANGUAGE,
        );
        if (ACMS_SID) {
            $phpSession = Session::handle();
            $phpSession->writeClose(); // セッションをクローズ（デッドロック対応）
            $headerAry[] = 'Cookie: ' . SESSION_NAME . '=' . ACMS_SID;
        }
        $url = acmsLink($Q, true, true);

        try {
            $contents = '';
            $req = \Http::init($url, 'GET');
            $req->setRequestHeaders($headerAry);
            $response = $req->send();
            if (strpos(\Http::getResponseHeader('http_code'), '200') === false) {
                throw new \RuntimeException(\Http::getResponseHeader('http_code'));
            }
            $responseHeaders = $response->getResponseHeader();
            $contents = $response->getResponseBody();
            $contentType = isset($responseHeaders['Content-Type']) ? $responseHeaders['Content-Type'] : '';
            $contentType = isset($responseHeaders['content-type']) ? $responseHeaders['content-type'] : '';
            if (
                1
                and $contentType
                and preg_match('@^text/[^;]+; charset=(.*)$@', $contentType, $match)
            ) {
                $contents = mb_convert_encoding($contents, 'UTF-8', $match[1]);
            }
            if ($toCharset = $this->Post->get('charset')) {
                $contents = mb_convert_encoding($contents, $toCharset, 'UTF-8');
            }
            header('Content-Length: ' . strlen($contents));
            if (strpos(UA, 'MSIE')) {
                header('Content-Type: text/download');
            } else {
                header('Content-Disposition: attachment');
                header('Content-Type: application/octet-stream');
            }
            die($contents);
        } catch (\Exception $e) {
            AcmsLogger::warning('ダウンロードに失敗しました', Common::exceptionArray($e, ['url' => $url]));
            echo $e->getMessage();
        }
    }
}
