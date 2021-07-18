<?php

namespace Acms\Services\StaticExport\Generator;

use Acms\Services\StaticExport\Contracts\Generator;
use Acms\Services\Facades\Storage;
use SQL;
use DB;
use ACMS_Filter;

class CategoryGenerator extends Generator
{
    /**
     * @var int
     */
    protected $numberOfCategories;

    /**
     * @var array
     */
    protected $targetCategories;

    /**
     * @param array $categories
     */
    public function setTargetCategories($categories)
    {
        $this->targetCategories = $categories;
    }

    protected function getName()
    {
        return 'カテゴリートップの書き出し';
    }

    protected function getTasks()
    {
        return $this->numberOfCategories;
    }

    /**
     * @return void
     */
    protected function main()
    {
        $this->numberOfCategories = count($this->targetCategories);

        $this->logger->start($this->getName(), $this->getTasks());

        foreach ($this->targetCategories as $cid) {
            $info = array(
                'bid' => BID,
                'cid' => $cid,
            );
            $url = acmsLink($info);
            try {
                $this->request($url, array($info, 'index.html'));
            } catch ( \Exception $e ) {
                $this->logger->error($e->getMessage(), $url);
            }
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
            $this->logger->error('データの取得に失敗しました。', acmsLink($info[0]), $code);
            return;
        }
        $destination = $this->destination->getDestinationPath() . $this->destination->getBlogCode();
        $blog_url = acmsLink(array('bid'=>BID));
        $url = acmsLink($info[0]);
        $dir = substr($url, strlen($blog_url));

        try {
            Storage::makeDirectory($destination . $dir);
            Storage::put($destination . $dir . $info[1], $data);
        } catch ( \Exception $e ) {
            $this->logger->error('データの書き込みに失敗しました。', $destination . $dir . 'index.html');
        }
    }
}