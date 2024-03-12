<?php

namespace Acms\Services\StaticExport\Generator;

use Acms\Services\StaticExport\Contracts\Generator;
use Acms\Services\Facades\Storage;

class PageGenerator extends Generator
{
    /**
     * @var int
     */
    protected $maxPage;

    /**
     * @param int $max
     */
    public function setMaxPage($max)
    {
        $this->maxPage = $max;
    }

    /**
     * @return void
     */
    protected function main()
    {
        if (empty($this->maxPage)) {
            throw  new \RuntimeException('no selected max page.');
        }

        for ($page = 2; $page <= $this->maxPage; $page++) {
            $info = array(
                'bid' => BID,
                'page' => $page,
            );
            $url = acmsLink($info);
            $this->request($url, $info);
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
        if ($code != '200') {
            return;
        }
        $destination = $this->destination->getDestinationPath() . $this->destination->getBlogCode();

        try {
            Storage::makeDirectory($destination);
            Storage::put($destination . 'page' . $info['page'] . '.html', $data);
        } catch (\Exception $e) {
            $this->logger->error('データの書き込みに失敗しました。', $destination . 'page' . $info['page'] . '.html');
        }
    }
}
