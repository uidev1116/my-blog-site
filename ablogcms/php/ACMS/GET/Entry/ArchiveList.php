<?php

class ACMS_GET_Entry_ArchiveList extends ACMS_GET_Entry
{
    /**
     *  @var int
     */
    protected $limit;

    /**
     *  @var 'month' | 'day' | 'year' | 'biz_year'
     */
    protected $chunk;

    /**
     * @inheritDoc
     */
    public $_axis = [ // phpcs:ignore
        'bid'   => 'self',
        'cid'   => 'self',
    ];

    public function initVars()
    {
        $this->order    = config('entry_archive_list_order');
        $this->limit    = intval(config('entry_archive_list_limit'));
        $this->chunk    = config('entry_archive_list_chunk');
    }

    public function get()
    {
        $this->initVars();

        $date = $this->Q->getArray('date');

        $DB = DB::singleton(dsn());
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $this->buildModuleField($Tpl);

        /*
        * substring for datetime
        */
        switch ($this->chunk) {
            case 'year':
                $substr = 4;
                break;
            case 'month':
                $substr = 7;
                break;
            case 'day':
                $substr = 10;
                break;
            case 'biz_year':
                $substr = 7;
                $biz_year = isset($date[0]) && !empty($date[0]) ? $date[0] : date('Y');
                if (isset($date[1]) && !empty($date[1])) {
                    $biz_year = date('Y', strtotime($date[0] . '-' . $date[1] . '-01 -3month'));
                }
                $this->start = $biz_year++ . '-04-01 00:00:00';
                $this->end   = $biz_year . '-03-31 23:59:59';
                $this->limit = 12;
                break;
        }

        $SQL = SQL::newSelect('entry');
        $SQL->addLeftJoin('category', 'category_id', 'entry_category_id');
        $SQL->addLeftJoin('blog', 'blog_id', 'entry_blog_id');

        ACMS_Filter::categoryTree($SQL, $this->cid, $this->categoryAxis());
        ACMS_Filter::categoryStatus($SQL);
        ACMS_Filter::blogTree($SQL, $this->bid, $this->blogAxis());
        ACMS_Filter::blogStatus($SQL);

        $SQL->addSelect('entry_datetime');
        $SQL->addSelect(SQL::newFunction('entry_datetime', ['SUBSTR', 0, $substr]), 'entry_date');
        $SQL->addSelect('entry_id', 'entry_amount', null, 'count');
        $SQL->addGroup('entry_date');
        $SQL->addOrder('entry_date', $this->order);

        ACMS_Filter::entrySession($SQL);
        ACMS_Filter::entrySpan($SQL, $this->start, $this->end);

        if (!empty($this->tags)) {
            ACMS_Filter::entryTag($SQL, $this->tags);
        }
        if (!empty($this->keyword)) {
            ACMS_Filter::entryKeyword($SQL, $this->keyword);
        }
        if (!empty($this->Field)) {
            ACMS_Filter::entryField($SQL, $this->Field);
        }
        $SQL->addWhereOpr('entry_indexing', 'on');

        $SQL->setLimit($this->limit);


        $all = $DB->query($SQL->get(dsn()), 'all');

        foreach ($all as $row) {
            switch ($this->chunk) {
                case 'year':
                    $row['entry_date'] = $row['entry_date'] . '-01-01 00:00:00';
                    $y = date('Y', strtotime($row['entry_date']));
                    $m = null;
                    $d = null;
                    break;
                case 'month':
                    $row['entry_date'] = $row['entry_date'] . '-01 00:00:00';
                    $y = date('Y', strtotime($row['entry_date']));
                    $m = date('m', strtotime($row['entry_date']));
                    $d = null;
                    break;
                case 'day':
                    $row['entry_date'] = $row['entry_date'] . ' 00:00:00';
                    $y = date('Y', strtotime($row['entry_date']));
                    $m = date('m', strtotime($row['entry_date']));
                    $d = date('d', strtotime($row['entry_date']));
                    break;
                case 'biz_year':
                    $row['entry_date'] = $row['entry_date'] . '-01 00:00:00';
                    $y = date('Y', strtotime($row['entry_date']));
                    $m = date('m', strtotime($row['entry_date']));
                    $d = null;
                    break;
            }

            $vars = [
                'amount'    => $row['entry_amount'],
                'chunkDate' => $row['entry_date'],
                'url'       => acmsLink([
                    'bid'   => $this->bid,
                    'cid'   => $this->cid,
                    'date'  => [$y, $m, $d]
                ]),
            ];

            $vars += $this->buildDate(date('Y-m-d H:i:s', strtotime($row['entry_date'])), $Tpl, 'archive:loop');

            $Tpl->add('archive:loop', $vars);
        }
        return $Tpl->get();
    }
}
