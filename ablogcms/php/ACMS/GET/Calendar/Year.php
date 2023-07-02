<?php

class ACMS_GET_Calendar_Year extends ACMS_GET
{
    var $_axis  = array(
        'bid'   => 'self',
        'cid'   => 'self',
    );

    var $_scope = array(
        'date'  => 'global',
        'start' => 'global',
        'end'   => 'global',
    );

    function buildMonth( & $Tpl, $ym, $block=array() )
    {
        if ( '1000-01' == $ym ) $ym = date('Y-m', requestTime());
        list($y, $m)    = explode('-', $ym);

        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('entry');

        $SQL->addSelect(SQL::newFunction('entry_datetime', array('SUBSTR', 0, 10)), 'entry_date', null, 'DISTINCT');
        $SQL->addLeftJoin('blog', 'blog_id', 'entry_blog_id');
        ACMS_Filter::blogTree($SQL, $this->bid, $this->blogAxis());
        ACMS_Filter::blogStatus($SQL);
        $SQL->addLeftJoin('category', 'category_id', 'entry_category_id');
        ACMS_Filter::categoryTree($SQL, $this->cid, $this->categoryAxis());
        ACMS_Filter::categoryStatus($SQL);
        ACMS_Filter::entrySession($SQL);
        ACMS_Filter::entrySpan($SQL, $ym.'-01 00:00:00', $ym.'-31 23:59:59');
        $q  = $SQL->get(dsn());

        $exists = array();
        $all    = $DB->query($q, 'all');
        foreach ( $all as $row ) {
            $exists[]   = $row['entry_date'];
        }

        $beginW = intval(config('calendar_year_begin_week'));
        $endW   = (6 + $beginW) % 7;
        $label  = configArray('calendar_year_week_label');
        
        for ( $i=0; $i<7; $i++ ) {
            $w  = ($beginW + $i) % 7;
            $Tpl->add(array_merge(array('weekLabel:loop'), $block), array(
                'w'     => $w,
                'label' => $label[$w],
            ));

        }

        //--------
        // spacer
        $firstW     = intval(date('w', strtotime($ym.'-01')));

        if ( $span = ($firstW + (7 - $beginW)) % 7 ) {
            for ( $i=0; $i<$span; $i++ ) {
                $Tpl->add(array_merge(array('spacer'), $block));
                $Tpl->add(array_merge(array('day:loop'), $block));
            }
        }

        //-----
        // day
        $lastDay    = intval(date('t', strtotime($ym.'-01')));
        $curW   = $firstW;
        for ( $day=1; $day<=$lastDay; $day++ ) {
            $date   = $ym.'-'.sprintf('%02d', $day);
            if ( in_array($date, $exists) ) {
                $Tpl->add(array_merge(array('link'), $block), array(
                    'w'     => $curW,
                    'url'   => acmsLink(array(
                        'bid'   => $this->bid,
                        'cid'   => $this->cid,
                        'date'  => array(
                            intval($y), intval($m), intval($day)
                        ),
                    )),
                    'day'   => $day,
                ));
            } else {
                $Tpl->add(array_merge(array('none'), $block), array(
                    'w'     => $curW,
                    'day'   => $day,
                ));
            }
            $Tpl->add(array_merge(array('day:loop'), $block));
            $curW   = ($curW + 1) % 7;
            if ( $beginW == $curW ) $Tpl->add(array_merge(array('week:loop'), $block));
        }

        //--------
        // spacer
        $lastW  = ($curW + 6) % 7;

        if ( $span = 6 - ($lastW + (7 - $beginW)) % 7 ) {
            for ( $i=0; $i<$span; $i++ ) {
                $Tpl->add(array_merge(array('spacer'), $block));
                $Tpl->add(array_merge(array('day:loop'), $block));
            }
            $Tpl->add('week:loop');
        }
        return empty($exists) ? false : true;
    }

    function get()
    {
        $DB     = DB::singleton(dsn());

        $ym     = substr($this->start, 0, 7);
        if ( '1000-01' == $ym ) $ym = date('Y-m', requestTime());
        list($y, $m)    = explode('-', $ym);

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $this->buildModuleField($Tpl);
        $unit   = config('calendar_year_unit', 1);

        for ( $mo=0; $mo<12; $mo++ ) {
            $this->buildMonth($Tpl, $y.'-'.sprintf('%02d', $mo + 1), array('month:loop', 'unit:loop'));
            $Tpl->add(array('month:loop', 'unit:loop'), array(
                'month'     => $mo + 1,
                'monthDate' => $y.'-'.sprintf('%02d-01', $mo + 1),
                'monthUrl'  => acmsLink(array(
                    'bid'   => $this->bid,
                    'cid'   => $this->cid,
                    'date'  => date("Y/m", strtotime($y.'-'.sprintf('%02d-01', $mo + 1))),
                )),
            ));
            if ( $mo % $unit === $unit - 1 ) {
                $Tpl->add('unit:loop');
            }
        }
        if ( 12 % $unit !== 0 ) {
            $Tpl->add('unit:loop');
        }
        $prevtime   = mktime(0, 0, 0, intval($m), 1, intval($y) - 1);
        $nexttime   = mktime(0, 0, 0, intval($m), 1, intval($y) + 1);

        $vars   = array('year' => $y);

        //-----------
        // year link
        $SQL    = SQL::newSelect('entry');
        $SQL->addSelect('entry_datetime');
        $SQL->addLeftJoin('blog', 'blog_id', 'entry_blog_id');
        ACMS_Filter::blogTree($SQL, $this->bid, $this->blogAxis());
        ACMS_Filter::blogStatus($SQL);
        $SQL->addLeftJoin('category', 'category_id', 'entry_category_id');
        ACMS_Filter::categoryTree($SQL, $this->cid, $this->categoryAxis());
        ACMS_Filter::categoryStatus($SQL);
        ACMS_Filter::entrySession($SQL);
        
        $Recently   = clone $SQL;
        $Recently->addOrder('entry_datetime', 'DESC');
        $recentryValue = $DB->query($Recently->get(dsn()), 'one');
        if ( intval($recentryValue) < intval(date('Y', requestTime())) ) {
            $recentryValue = intval(date('Y', requestTime()));
        }

        $Past       = clone $SQL;
        $Past->addOrder('entry_datetime', 'ASC');
        $pastValue  = $DB->query($Past->get(dsn()), 'one');
        if ( !$pastValue ) {
            $pastValue = date('Y', requestTime());
        }

        //-----------
        // prev link
        if ( ($y - 1) >= intval($pastValue) ) {
            $Tpl->add('prevLink', array(
                'pYear'  => $y - 1,
                'url'   => acmsLink(array(
                    'bid'   => $this->bid,
                    'cid'   => $this->cid,
                    'tpl'   => TPL,
                    'date' => array($y - 1),
                )),
            ));
        }

        //-----------
        // next link
        if ( ($y + 1) <= intval($recentryValue) ) {
            $Tpl->add('nextLink', array(
                'nYear'  => $y + 1,
                'url'   => acmsLink(array(
                    'bid'   => $this->bid,
                    'cid'   => $this->cid,
                    'tpl'   => TPL,
                    'date' => array($y + 1),
                )),
            ));
        }

        $Tpl->add(null, $vars);

        return $Tpl->get();
    }
}
