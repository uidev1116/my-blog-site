<?php

namespace Acms\Services\StaticExport\Generator;

use Acms\Services\StaticExport\Contracts\Generator;
use Acms\Services\Facades\Storage;
use DB;
use SQL;
use ACMS_Filter;

class CategoryArchivesGenerator extends Generator
{
    /**
     * @var string
     */
    protected $categoryId;

    /**
     * @var array
     */
    protected $monthRange;

    /**
     * @var int
     */
    protected $maxPage;

    protected function getName()
    {
        if ($this->categoryId) {
            return 'カテゴリー毎のアーカイブ書き出し ( ' . \ACMS_RAM::categoryName($this->categoryId) . ' )';
        } else {
            return 'カテゴリーなしのアーカイブ書き出し';
        }
    }

    protected function getTasks()
    {
        return count($this->monthRange);
    }

    /**
     * @param int $cid
     */
    public function setCategoryId($cid)
    {
        $this->categoryId = $cid;
    }

    /**
     * @param $start
     * @throws \Exception
     */
    public function setMonthRange($start)
    {
        $this->monthRange = array();
        $start = (new \DateTime)->setTimestamp(strtotime($start));
        $end = false;

        // そのカテゴリーの最後の日付のエントリーまでアーカイブを作る
        $SQL = SQL::newSelect('entry');
        $SQL->addLeftJoin('category', 'category_id', 'entry_category_id');
        $SQL->addSelect('entry_datetime');
        $SQL->addWhereOpr('entry_status', 'open');
        $SQL->setOrder('entry_datetime', 'DESC');
        $SQL->setLimit(1);
        if (intval($this->categoryId) > 0) {
            $SQL->addWhereOpr('category_status', 'open');
            ACMS_Filter::categoryTree($SQL, $this->categoryId, 'descendant-or-self');
        }
        if ($last = DB::query($SQL->get(dsn()), 'one')) {
            $last = date('Y-m-31 23:23:59', strtotime($last));
            $end = (new \DateTime)->setTimestamp(strtotime($last));
        }
        if (empty($end)) {
            $end = (new \DateTime)->setTimestamp(REQUEST_TIME);
        }
        $next_month = new \DateInterval('P1M');

        while ( $start < $end ) {
            $year = $start->format('Y');
            $month = $start->format('m');
            if ( array_search($year, $this->monthRange) === false ) {
                $this->monthRange[] = $year;
            }
            $this->monthRange[] = $year . '/' . $month;
            $start->add($next_month);
        }
    }

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
        if ( empty($this->monthRange) ) {
            throw new \RuntimeException('no selected month.');
        }
        if ( empty($this->maxPage) ) {
            throw  new \RuntimeException('no selected max page.');
        }

        $this->logger->start($this->getName(), $this->getTasks());

        foreach ( $this->monthRange as $ym ) {
            $info = array(
                'bid' => BID,
                'date' => $ym,
            );
            if ($this->categoryId) {
                $info['cid'] = $this->categoryId;
            }
            $url = acmsLink($info, false);
            $this->request($url, $info);
            for ( $page=2; $page<=$this->maxPage; $page++ ) {
                $info['page'] = $page;
                $url = acmsLink($info);
                $this->request($url, $info);
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
        if ( $code != '200' ) {
            return;
        }
        $destination = $this->destination->getDestinationPath() . $this->destination->getBlogCode();
        $page = isset($info['page']) ? intval($info['page']) : 1;
        $file = 'index.html';
        if ( $page > 1 ) {
            $file = 'page' . $page . '.html';
        } else if ( $this->logger ) {
            $this->logger->processing();
        }
        unset($info['page']);
        $blog_url = acmsLink(array('bid'=>BID));
        $archive_url = acmsLink($info);
        $dir = substr($archive_url, strlen($blog_url));

        try {
            Storage::makeDirectory($destination . $dir);
            Storage::put($destination . $dir . $file, $data);
        } catch ( \Exception $e ) {
            $this->logger->error('データの書き込みに失敗しました。', $destination . $dir . $file);
        }

    }
}