<?php

class ACMS_GET_Entry_Calendar extends ACMS_GET
{
    protected $config;
    protected $entries;

    protected $ymd;
    protected $y;
    protected $m;
    protected $d;

    /**
     * カレンダーの開始日
     * 例）前後月のエントリー表示を有効にしている場合、2022/06 のURLコンテキストでアクセスしたとき 2022-05-30 00:00:00
     *
     * @var string
     */
    protected $startDate;

    /**
     * カレンダーの終了日
     * 例）前後月のエントリー表示を有効にしている場合、2022/06 のURLコンテキストでアクセスしたとき 2022-07-03 23:59:59
     *
     * @var string
     */
    protected $endDate;

    /**
     * エントリーの開始日
     *
     * @var string
     */
    protected $entryStartDate;

    /**
     * エントリーの終了日
     *
     * @var string
     */
    protected $entryEndDate;

    /**
     * day:loop ブロックの中で最も若い日
     *
     * @var int
     */
    protected $firstDay;

    /**
     * day:loop ブロックのループ回数
     *
     * @var int
     */
    protected $loopCount;

    /**
     * day:loop ブロックの中で最も若い日の曜日（数値）
     *
     * @var int
     */
    protected $firstW;

    /**
     * 前後月を考慮した最初の曜日（数値）
     *
     * @var int
     */
    protected $beginW;

    /**
     * week:loopブロックの区切りの曜日（数値）
     *
     * @var int
     */
    protected $separateWeek;

    public $_axis  = array(
        'bid'   => 'self',
        'cid'   => 'self'
    );

    public $_scope = array(
        'date'  => 'global',
        'start' => 'global',
        'end'   => 'global'
    );

    /**
     * コンフィグの取得
     *
     * @return array
     */
    protected function initVars()
    {
        return [
            'mode' => config('entry_calendar_mode'),
            'pagerCount' => intval(config('entry_calendar_pager_count')) === 0
                                ? 7
                                : intval(config('entry_calendar_pager_count')),
            'order' => config('entry_calendar_order'),
            'aroundEntry' => config('entry_calendar_around'),
            'beginWeek' => intval(config('entry_calendar_begin_week')),
            'maxEntryCount' => intval(config('entry_calendar_max_entry_count')) === 0
                                    ? 3
                                    : intval(config('entry_calendar_max_entry_count')),
            'weekLabels' => configArray('entry_calendar_week_label'),
            'today' => config('entry_calendar_today'),
            'dateOrder' => config('entry_calendar_date_order')
        ];
    }

    /**
     * 日付のURLコンテキストに関わるの代入
     *
     * @return void
     */
    protected function setDateContextVars()
    {
        $this->ymd = substr($this->start, 0, 10) === '1000-01-01'
                ? date('Y-m-d', requestTime())
                : substr($this->start, 0, 10);

        list($this->y, $this->m, $this->d) = explode('-', $this->ymd);
    }

    /**
     * カレンダーの計算に必要な変数の代入
     *
     * @return void
     */
    protected function setCalendarVars()
    {
        switch ($this->config['mode']) {
            case "month":
                $ym = substr($this->ymd, 0, 7);
                $this->startDate = $ym . '-01 00:00:00';
                $this->endDate = $ym . '-31 23:59:59';

                $this->entryStartDate = $this->startDate;
                $this->entryEndDate = $this->endDate;

                $this->firstDay = 1;

                $this->loopCount = intval(date('t', strtotime($ym . '-01')));

                $this->firstW = intval(date('w', strtotime($ym . '-01')));
                $this->beginW = intval($this->config['beginWeek']);
                $prevS = ($this->firstW + (7 - $this->beginW)) % 7;

                $this->startDate = $this->computeDate(
                    (int)substr($this->startDate, 0, 4),
                    (int)substr($this->startDate, 5, 2),
                    (int)substr($this->startDate, 8, 2),
                    -$prevS
                );
                $this->startDate = $this->startDate . ' 00:00:00';

                $lastW = intval(date('w', strtotime($this->endDate)));
                $nextS = 6 - ($lastW + (7 - $this->beginW)) % 7;
                $this->endDate = $this->computeDate(
                    (int)substr($this->endDate, 0, 4),
                    (int)substr($this->endDate, 5, 2),
                    (int)substr($this->endDate, 8, 2),
                    $nextS
                );
                $this->endDate = $this->endDate . ' 23:59:59';

                if ($this->config['aroundEntry'] === 'on') {
                    $this->entryStartDate = $this->startDate;
                    $this->entryEndDate = $this->endDate;
                }

                $this->firstW = intval(date('w', strtotime($ym . '-01')));
                $this->beginW = intval($this->config['beginWeek']);

                // day:loop で曜日が1巡する前にweek:loopを追加する
                $this->separateWeek = ($this->beginW - 1 < 0) ? 6 : $this->beginW - 1;

                /**
                 * entry_calendar_date_orderがdescのときは逆から数えるため1つ前の曜日ではなく
                 * 前後月を考慮した最初の曜日を区切りの曜日とする
                 */
                if ($this->config['dateOrder'] === 'desc') {
                    $this->separateWeek = $this->beginW;
                }

                break;

            case "week":
                $week = intval(date('w', strtotime($this->ymd)));
                $prevNum = ($week >= $this->config['beginWeek']) ? $week - $this->config['beginWeek'] : 7 - ($this->config['beginWeek'] - $week);
                $minusDay = $this->computeDate(
                    (int)substr($this->ymd, 0, 4),
                    (int)substr($this->ymd, 5, 2),
                    (int)substr($this->ymd, 8, 2),
                    -$prevNum
                );
                $addDay = $this->computeDate(
                    (int)substr($this->ymd, 0, 4),
                    (int)substr($this->ymd, 5, 2),
                    (int)substr($this->ymd, 8, 2),
                    6 - $prevNum
                );

                $this->startDate = $minusDay . ' 00:00:00';
                $this->endDate = $addDay . ' 23:59:59';

                $this->firstDay = substr($minusDay, 8, 2);

                $this->entryStartDate = $this->startDate;
                $this->entryEndDate = $this->endDate;

                $this->loopCount = 7;

                $this->firstW = intval(date('w', strtotime($this->startDate)));
                $this->beginW = intval($this->config['beginWeek']);

                $this->separateWeek = -1; // week:loopブロックを追加しない

                break;

            case "days":
                $addDay = $this->computeDate(
                    (int)substr($this->ymd, 0, 4),
                    (int)substr($this->ymd, 5, 2),
                    (int)substr($this->ymd, 8, 2),
                    6
                );
                $this->startDate = $this->ymd . ' 00:00:00';
                $this->endDate = $addDay . ' 23:59:59';

                $this->firstDay = substr($this->ymd, 8, 2);

                $this->loopCount = 7;

                $this->entryStartDate = $this->startDate;
                $this->entryEndDate = $this->endDate;

                $this->firstW = intval(date('w', strtotime($this->startDate)));
                $this->beginW = intval(date('w', strtotime($this->startDate)));

                $this->separateWeek = -1; // week:loopブロックを追加しない

                break;

            case "until_days":
                $minusDay = $this->computeDate(
                    (int)substr($this->ymd, 0, 4),
                    (int)substr($this->ymd, 5, 2),
                    (int)substr($this->ymd, 8, 2),
                    -6
                );

                $this->startDate = $minusDay . ' 00:00:00';
                $this->endDate = $this->ymd . ' 23:59:59';

                $this->firstDay = substr($minusDay, 8, 2);

                $this->loopCount = 7;

                $this->entryStartDate = $this->startDate;
                $this->entryEndDate = $this->endDate;

                $this->firstW = intval(date('w', strtotime($this->startDate)));
                $this->beginW = intval(date('w', strtotime($this->startDate)));

                $this->separateWeek = -1; // week:loopブロックを追加しない

                break;
        }
    }

    function get()
    {
        if (!$this->setConfig()) {
            return '';
        }

        $DB = DB::singleton(dsn());
        $Tpl = new Template($this->tpl, new ACMS_Corrector());

        $this->buildModuleField($Tpl);

        $this->setDateContextVars();
        $this->setCalendarVars();

        $q = $this->buildQuery();
        $this->entries = $DB->query($q, 'all');

        $this->buildWeekLabel($Tpl);

        if ($this->config['mode'] === 'month') {
            // spacer
            $this->buildForeSpacer($Tpl);
        }

        $this->buildDays($Tpl);

        if ($this->config['mode'] === 'month') {
            // spacer
            $this->buildRearSpacer($Tpl);
        }

        $Tpl->add('date', $this->getDateVars());

        return $Tpl->get();
    }

    function computeDate($year, $month, $day, $addDays)
    {
        $baseSec = mktime(0, 0, 0, $month, $day, $year);
        $addSec = $addDays * 86400;
        $targetSec = $baseSec + $addSec;

        return date('Y-m-d', $targetSec);
    }

    /**
     * コンフィグのセット
     *
     * @return bool
     */
    protected function setConfig()
    {
        $this->config = $this->initVars();
        if ($this->config === false) {
            return false;
        }

        return true;
    }

    /**
     * sqlの組み立て
     *
     * @return SQL_Select
     */
    protected function buildQuery()
    {
        $SQL = SQL::newSelect('entry');
        $SQL->addSelect(SQL::newFunction('entry_datetime', array('SUBSTR', 0, 10)), 'entry_date', null, 'DISTINCT');
        $SQL->addSelect('entry_id');
        $SQL->addSelect('entry_approval');
        $SQL->addSelect('entry_title');
        $SQL->addSelect('entry_category_id');
        $SQL->addSelect('entry_blog_id');
        $SQL->addSelect('entry_link');
        $SQL->addSelect('entry_datetime');
        $SQL->addSelect('entry_start_datetime');
        $SQL->addSelect('entry_end_datetime');
        $SQL->addSelect('entry_status');
        $SQL->addLeftJoin('blog', 'blog_id', 'entry_blog_id');
        ACMS_Filter::blogTree($SQL, $this->bid, $this->blogAxis());
        ACMS_Filter::blogStatus($SQL);
        $SQL->addLeftJoin('category', 'category_id', 'entry_category_id');

        ACMS_Filter::categoryTree($SQL, $this->cid, $this->categoryAxis());
        ACMS_Filter::categoryStatus($SQL);
        ACMS_Filter::entrySession($SQL);
        ACMS_Filter::entrySpan($SQL, $this->entryStartDate, $this->entryEndDate);
        ACMS_Filter::entryOrder($SQL, $this->config['order'], $this->uid, $this->cid);

        return $SQL->get(dsn());
    }

    /**
     * weekLabelループブロックの組み立て
     *
     * @param Template & $Tpl
     * @return bool
     */
    protected function buildWeekLabel(&$Tpl)
    {
        for ($i = 0; $i < 7; $i++) {
            $w  = ($this->beginW + $i) % 7;
            if (!empty($this->config['weekLabels'][$w])) {
                $Tpl->add('weekLabel:loop', array(
                    'w'     => $w,
                    'label' => $this->config['weekLabels'][$w],
                ));
            }
        }

        return true;
    }

    /**
     * 前月のspacerの組み立て
     *
     * @param Template & $Tpl
     * @return bool
     */
    protected function buildForeSpacer(&$Tpl)
    {
        if ($this->config['dateOrder'] !== 'desc') {
            $span = ($this->firstW + (7 - $this->beginW)) % 7;
            $date = substr($this->startDate, 0, 10);
        } else {
            $lastW  = intval(date('w', strtotime('last day of' . substr($this->ymd, 0, 7))));
            $span = 6 - ($lastW + (7 - $this->beginW)) % 7;

            $date = substr($this->endDate, 0, 10);
        }

        if ($span === 0) {
            return false;
        }

        for ($i = 0; $i < $span; $i++) {
            $pw = intval(date('w', strtotime($date)));
            if (!empty($this->config['weekLabels'][$pw])) {
                $this->buildEntries($Tpl, $date, ['foreEntry:loop', 'foreSpacer'], $this->config['aroundEntry'] === 'on');

                $Tpl->add(['foreSpacer', 'week:loop'], array(
                    'prevDay'   => intval(substr($date, 8, 2)), // 先頭の0削除
                    'prevDate'  => $date,
                    'w'         => $pw,
                    'week'      => $this->config['weekLabels'][$pw],
                ));

                $date = date('Y-m-d', strtotime($date . (($this->config['dateOrder'] === 'desc') ? '-' : '') . '1 day'));
            }
        }

        return true;
    }

    /**
     * 翌月のspacerの組み立て
     *
     * @param Template & $Tpl
     * @return bool
     */
    protected function buildRearSpacer(&$Tpl)
    {
        if ($this->config['dateOrder'] !== 'desc') {
            $lastW  = intval(date('w', strtotime('last day of' . substr($this->ymd, 0, 7))));
            $span = 6 - ($lastW + (7 - $this->beginW)) % 7;

            $date = substr($this->endDate, 0, 7) . '-01';
        } else {
            $span = ($this->firstW + (7 - $this->beginW)) % 7;
            $date = date('Y-m-d', strtotime('last day of' . substr($this->startDate, 0, 7)));
        }

        if ($span === 0) {
            return false;
        }

        for ($i = 0; $i < $span; $i++) {
            $nw = intval(date('w', strtotime($date)));
            if (!empty($this->config['weekLabels'][$nw])) {
                $this->buildEntries($Tpl, $date, ['rearEntry:loop', 'rearSpacer'], $this->config['aroundEntry'] === 'on');

                $Tpl->add(['rearSpacer', 'week:loop'], array(
                    'nextDay' => intval(substr($date, 8, 2)), // 先頭の0削除
                    'nextDate' => $date,
                    'w'       => $nw,
                    'week'    => $this->config['weekLabels'][$nw],
                ));

                $date = date('Y-m-d', strtotime($date . (($this->config['dateOrder'] === 'desc') ? '-' : '') . '1 day'));
            }
        }

        $Tpl->add('week:loop');
        return true;
    }

    /**
     * 日付のループの組み立て
     *
     * @param Template & $Tpl
     * @return void
     */
    protected function buildDays(&$Tpl)
    {
        $date = date('Y-m-d', strtotime($this->startDate . strval(($this->firstW + (7 - $this->beginW)) % 7) . 'day'));

        if ($this->config['dateOrder'] === 'desc') {
            $date = date('Y-m-d', strtotime($date . strval($this->loopCount - 1) . ' day'));
        }

        for ($i = 0; $i < intval($this->loopCount); $i++) {
            $curW = intval(date('w', strtotime($date)));

            $vars = array(
                'week'  => $this->config['weekLabels'][$curW],
                'w'     => $curW,
                'day'   => intval(substr($date, 8, 2)), // 先頭の0削除
                'date'  => $date,
            );

            if (date('Y-m-d', requestTime()) === $date) {
                $vars += array(
                    'today' => $this->config['today']
                );
            }

            $this->buildEntries($Tpl, $date, ['entry:loop', 'day:loop']);

            if (!empty($this->config['weekLabels'][$curW])) {
                $Tpl->add(
                    array_merge(
                        ['day:loop'],
                        $this->config['mode'] === 'month' ? ['week:loop'] : []
                    ),
                    $vars
                );
            }

            $date = date('Y-m-d', strtotime($date . (($this->config['dateOrder'] === 'desc') ? '-' : '') . '1 day'));

            if ($this->separateWeek === $curW) {
                $Tpl->add('week:loop');
            }
        }
    }

    /**
     * エントリーのループの組み立て
     *
     * @param Template & $Tpl
     * @param string $date
     * @param string|array $block
     * @param bool|null $enableAround
     * @return void
     */
    protected function buildEntries(&$Tpl, $date, $block, $enableAround = null)
    {
        $currentEntries = array();
        foreach ($this->entries as $entry) {
            if ($entry['entry_date'] === $date) {
                $currentEntries[] = array(
                    'eid'       => $entry['entry_id'],
                    'cid'       => $entry['entry_category_id'],
                    'bid'       => $entry['entry_blog_id'],
                    'title'     => addPrefixEntryTitle(
                        $entry['entry_title'],
                        $entry['entry_status'],
                        $entry['entry_start_datetime'],
                        $entry['entry_end_datetime'],
                        $entry['entry_approval']
                    ),
                    'link'      => $entry['entry_link'],
                    'status'    => $entry['entry_status'],
                    'date'      => $entry['entry_datetime'],
                );
                if (count($currentEntries) == $this->config['maxEntryCount']) {
                    break;
                }
            }
        }

        if (count($currentEntries) !== 0) {
            foreach ($currentEntries as $entry) {
                $link   = $entry['link'];
                $link   = !empty($link) ? $link : acmsLink(array(
                    'bid' => $entry['bid'],
                    'eid' => $entry['eid'],
                ));
                $vars = array(
                    'eid'       => $entry['eid'],
                    'title'     => $entry['title'],
                    'cid'       => $entry['cid'],
                    'bid'       => $entry['bid'],
                    'status'    => $entry['status'],
                );

                //------
                // date
                $vars += $this->buildDate($entry['date'], $Tpl, $block);

                if ($link != '#') {
                    $Tpl->add(array_merge(array('url#rear'), $block));
                    $vars['url']  = $link;
                }
                $vars += $this->buildField(loadEntryField($entry['eid']), $Tpl, $block);
                $curW = intval(date('w', strtotime($date)));
                if (!empty($this->config['weekLabels'][$curW]) && (is_null($enableAround) || $enableAround)) {
                    $Tpl->add($block, $vars);
                }
            }
        }
    }

    /**
     * dateブロックの変数の取得
     *
     * @return array
     */
    protected function getDateVars()
    {
        $weekTitle = array();

        switch ($this->config['mode']) {
            case "month":
                $prevtime  = mktime(0, 0, 0, intval($this->m) - 1, 1, intval($this->y));
                $nexttime  = mktime(0, 0, 0, intval($this->m) + 1, 1, intval($this->y));
                list($py, $pm, $pd) = array(
                    date('Y', $prevtime),
                    date('m', $prevtime),
                    date('d', $prevtime)
                );
                list($ny, $nm, $nd) = array(
                    date('Y', $nexttime),
                    date('m', $nexttime),
                    date('d', $nexttime)
                );

                break;

            case "week":
                $prev = $this->computeDate(intval($this->y), intval($this->m), intval($this->d), -7);
                $next = $this->computeDate(intval($this->y), intval($this->m), intval($this->d), 7);
                list($py, $pm, $pd) = explode('-', $prev);
                list($ny, $nm, $nd) = explode('-', $next);
                $weekTitle = array(
                    'firstWeekDay' => $this->firstDay
                );

                break;

            case "days":
            case "until_days":
                $prev = $this->computeDate(intval($this->y), intval($this->m), intval($this->d), -$this->config['pagerCount']);
                $next = $this->computeDate(intval($this->y), intval($this->m), intval($this->d), $this->config['pagerCount']);
                list($py, $pm, $pd) = explode('-', $prev);
                list($ny, $nm, $nd) = explode('-', $next);
                $weekTitle = array(
                    'firstWeekDay' => $this->firstDay
                );

                break;
        }

        $vars = array(
            'year'      => $this->y,
            'month'     => $this->m,
            'day'       => substr($this->d, 0, 2),
            'prevDate'  => "$py/$pm/$pd",
            'nextDate'  => "$ny/$nm/$nd",
            'date'      => $this->ymd,
            'prevMonth' => $pm,
            'nextMonth' => $nm,
        );

        $vars += $weekTitle;

        return $vars;
    }
}
