<?php

namespace Acms\Services\StaticExport\Generator;

use Acms\Services\StaticExport\Contracts\Generator;
use Acms\Services\Facades\Storage;

class TopGenerator extends Generator
{
    protected function getName()
    {
        return 'トップページの書き出し';
    }

    protected function getTasks()
    {
        return 3;
    }

    /**
     * @return void
     */
    protected function main()
    {
        $this->logger->start($this->getName(), $this->getTasks());
        try {
            $url = acmsLink(array('bid'=>BID), false);
            $this->request($url, 'index.html');
            $this->request($url . 'rss2.xml', 'rss2.xml');
            $this->request($url . 'sitemap.xml', 'sitemap.xml');
        } catch ( \Exception $e ) {
            $this->logger->error($e->getMessage(), $url);
        }
    }

    /**
     * @param string $data
     * @param string $code
     * @param object $info
     * @return void
     */
    protected function callback($data, $code, $info)
    {
        if ( $this->logger ) {
            $this->logger->processing();
        }
        if ( empty($data) || $code != '200' ) {
            $this->logger->error('データの取得に失敗しました。', $info, $code);
            return;
        }
        try {
            Storage::put($this->destination->getDestinationPath() . $this->destination->getBlogCode() . $info, $data);
        } catch ( \Exception $e ) {
            $this->logger->error('データの書き込みに失敗しました。', $this->destination->getDestinationPath() . 'index.html');
        }
    }
}