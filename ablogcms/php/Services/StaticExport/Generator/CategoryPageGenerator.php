<?php

namespace Acms\Services\StaticExport\Generator;

use Acms\Services\StaticExport\Contracts\Generator;
use Acms\Services\Facades\Storage;

class CategoryPageGenerator extends PageGenerator
{
    /**
     * @var string
     */
    protected $categoryId;

    /**
     * @param int $cid
     */
    public function setCategoryId($cid)
    {
        $this->categoryId = $cid;
    }

    /**
     * @return void
     */
    protected function main()
    {
        if ( empty($this->categoryId) ) {
            throw new \RuntimeException('no selected category.');
        }
        if ( empty($this->maxPage) ) {
            throw  new \RuntimeException('no selected max page.');
        }

        for ( $page=2; $page<=$this->maxPage; $page++ ) {
            $info = array(
                'bid' => BID,
                'cid' => $this->categoryId,
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
        if ( $code != '200' ) {
            return;
        }
        $destination = $this->destination->getDestinationPath() . $this->destination->getBlogCode();
        $blog_url = acmsLink(array('bid'=>BID));
        $category_url = acmsLink(array('bid'=>BID, 'cid'=>$info['cid']));
        $dir = substr($category_url, strlen($blog_url));

        try {
            Storage::makeDirectory($destination . $dir);
            Storage::put($destination . $dir . 'page' . $info['page'] . '.html', $data);
        } catch ( \Exception $e ) {
            $this->logger->error('データの書き込みに失敗しました。', $destination . $dir . 'page' . $info['page'] . '.html');
        }
    }
}