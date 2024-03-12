<?php

namespace Acms\Services\Update\System;

use Acms\Services\Facades\Storage;

class Download
{
    /**
     * @var \Acms\Services\Update\Logger
     */
    protected $logger;

    /**
     * Download constructor.
     *
     * @param \Acms\Services\Update\Logger $logger
     */
    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $dest_path
     * @param string $url
     */
    public function download($dest_path, $url)
    {
        $this->logger->addMessage(gettext('パッケージをダウンロード中...') . ' (' . $url . ')', 5);

        set_time_limit(0);
        Storage::makeDirectory($dest_path);

        $filename = basename($url);
        $path = $dest_path . $filename;
        $fp = fopen($path, 'w+');

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_FILE, $fp);
        curl_setopt($curl, CURLOPT_TIMEOUT, 0);

        curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($status !== 200) {
            throw new \RuntimeException('Failed to download the package. ' . $status . ':' . $error);
        }

        $this->logger->addMessage(gettext('パッケージダウンロード完了'), 15);
        $this->logger->addMessage(gettext('パッケージを解凍中...'), 0);

        Storage::unzip($path, $dest_path);
        Storage::remove($path);

        $this->logger->addMessage(gettext('パッケージを解凍完了'), 30);
    }
}
