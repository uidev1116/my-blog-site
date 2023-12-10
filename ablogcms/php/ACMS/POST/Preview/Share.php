<?php

use Acms\Services\Facades\Preview;

class ACMS_POST_Preview_Share extends ACMS_POST
{
    protected $lifetime = 0;

    /**
     * run
     */
    function post()
    {
        try {
            $url = $this->Post->get('uri');
            $this->validate($url);

            Preview::expiredShareUrl();
            $lifetime = 60 * 60 * intval(config('url_preview_expire', 48));
            $shareUrl = Preview::getShareUrl($url, $lifetime);

            AcmsLogger::info('プレビュー共有URLを発行しました', [
                'url' => $url,
            ]);

            die (json_encode(array(
                'status' => true,
                'uri' => $shareUrl,
            )));
        } catch (\Exception $e) {
            AcmsLogger::notice('プレビュー共有URLの発行に失敗しました', [
                'url' => $url,
            ]);

            die (json_encode(array(
                'status' => false,
                'message' => $e->getMessage(),
            )));
        }
    }

    /**
     * @param string $url
     */
    protected function validate($url)
    {
        if (!sessionWithContribution()) {
            throw new \RuntimeException('Permission denied.');
        }
        if (empty($url)) {
            throw new \RuntimeException('Uri parameter empty.');
        }
    }
}
