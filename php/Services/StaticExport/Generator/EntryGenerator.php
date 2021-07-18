<?php

namespace Acms\Services\StaticExport\Generator;

use Acms\Services\StaticExport\CopyEntryArchive;
use DB;
use App;
use Acms\Services\StaticExport\Contracts\Generator;
use Acms\Services\Facades\Storage;

class EntryGenerator extends Generator
{
    /**
     * @var int
     */
    protected $numberOfEntries;

    /**
     * @var array
     */
    protected $targetEntries;

    /**
     * @var \Acms\Services\StaticExport\CopyEntryArchive
     */
    protected $copyArchiveEngine;

    /**
     * @var bool
     */
    protected $withArchive = false;

    /**
     * @param array $entries
     */
    public function setTargetEntries($entries)
    {
        $this->targetEntries = $entries;
    }

    /**
     * @param $bool
     */
    public function setWithArchive($bool)
    {
        $this->withArchive = $bool;
    }

    protected function getName()
    {
        return 'エントリーの書き出し';
    }

    protected function getTasks()
    {
        return $this->numberOfEntries;
    }

    /**
     * @return void
     */
    protected function main()
    {
        $this->numberOfEntries = count($this->targetEntries);
        $this->logger->start($this->getName(), $this->getTasks());

        if ($this->withArchive) {
            $this->copyArchiveEngine = new CopyEntryArchive($this->destination->getDestinationPath());
        }

        foreach ($this->targetEntries as $eid) {
            $info = array(
                'bid' => BID,
                'eid' => $eid,
            );
            $url = acmsLink($info);
            try {
                if ($this->withArchive) {
                    $this->copyArchiveEngine->copy($eid);
                }
                $this->request($url, $info);
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
            $this->logger->error('データの取得に失敗しました。', acmsLink($info), $code);
            return;
        }
        $destination = $this->destination->getDestinationPath() . $this->destination->getBlogCode();
        $blog_url = acmsLink(array('bid' => BID));
        $url = acmsLink($info);
        $file = substr($url, strlen($blog_url));
        $file = $destination . $file;

        if ( is_dir($file) ) {
            $file = $file . 'index.html';
        }
        try {
            Storage::makeDirectory(dirname($file));
            Storage::put($file, $data);
        } catch ( \Exception $e ) {
            $this->logger->error('データの書き込みに失敗しました。', $file);
        }
    }
}