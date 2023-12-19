<?php

class ACMS_GET_Plugin_Schedule extends ACMS_GET
{
    //現在値 or URLコンテキスト から取得＆生成する
    var $year;                  //年
    var $month;                 //月
    var $week;                  //週
    var $day;                   //日

    //変動制の生成値
    var $days = array();        //日々
    var $cnt_day;               //当月の日数 : buildDays()のみ
    var $_unit = 0;             //temporary

    //config に格納する
    var $unit;                  //月表示の折り返し 1 or 7
    var $forwardM;              //前方月数
    var $backM;                 //後方月数
    var $viewmode;              //デフォルトの表示
    var $listmode;
    var $formatY;
    var $formatM;
    var $formatD;
    var $formatW;
    var $labels;
    var $key;

    var $week_label = array(
      'JP'  => array( '日',  '月',  '火',  '水',  '木',  '金',  '土' ),
      //'EN'  => array( 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' ),
    );

    var $weekStart; // 七曜表の始まり

    function get()
    {
        $queryDate = $this->Q->getArray('date');

        $this->year   = isset($queryDate[0]) ? date('Y', mktime(0,0,0,1,1,$queryDate[0])) : date('Y');
        $this->month  = isset($queryDate[1]) ? date('m', mktime(0,0,0,$queryDate[1],1,$this->year)) : date('n');
        $this->day    = isset($queryDate[2]) ? date('d', mktime(0,0,0,$this->month,$queryDate[2],$this->year)) : 1;

        $this->sep      = config('schedule_label_separator');
        $this->layoutY  = config('schedule_layout_year');
        $this->layoutM  = config('schedule_layout_month');
        $this->layoutD  = config('schedule_layout_days');

        $this->forwardM = config('schedule_forwardM');
        $this->backM    = config('schedule_backM');
        $this->forwardD = config('schedule_forwardD');
        $this->backD    = config('schedule_backD');

        $this->viewmode = config('schedule_viewmode');

        $this->formatY  = config('schedule_formatY');
        $this->formatM  = config('schedule_formatM');
        $this->formatD  = config('schedule_formatD');
        $this->formatW  = config('schedule_formatW');

        $this->unit     = config('schedule_unit', 1);
        $this->key      = config('schedule_key');
        //$this->labels   = configArray('schedule_label@'.$this->key);

        $this->weekStart = config('schedule_week_start', 0);

        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('config');
        $SQL->addWhereOpr('config_key', 'schedule_label@'.$this->key);
        $SQL->addWhereOpr('config_blog_id', $this->bid);
        $labels = $DB->query($SQL->get(dsn()), 'all');
        foreach ( $labels as $label ) {
            $this->labels[] = $label['config_value'];
        }

        $this->week_label['JP'] = configArray('week_label');

        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        $this->buildModuleField($Tpl);

        if ( count($queryDate) == 1) { //年のみ
            $this->viewmode = 'year';
        } elseif ( count($queryDate) == 2 ) { //年月まで
            $this->viewmode = 'month';
        }

        switch( $this->viewmode ) {
            case 'year':
                $this->listmode = ( $this->layoutY == 'list' ) ? true : false;
                $this->yearView($Tpl);
                break;
            case 'month':
                $this->listmode = ( $this->layoutM == 'list' ) ? true : false;
                $this->monthView($Tpl);
                break;
            case 'days':
                $this->listmode = ( $this->layoutD == 'list' ) ? true : false;
                $this->day = date('d');
                $this->daysView($Tpl);
                break;
            default:
                return '';
        }

        return $Tpl->get();
    }

    function getWeekNum($cnt_week)
    {
        return config('schedule_weekRowNo').ceil($cnt_week/7);
    }

    function buildDays($year, $month, $week = null)
    {
        $this->cnt_day  = date('t', mktime(0,0,0,$month,1,$year));
        $cnt_week = null;

        $_n = 0;
        $_maxN = 6;
        $_w = 'w';
        if ( $this->weekStart == 1  ) {
            $_n = 1;
            $_maxN = 7;
            $_w = 'N';
        }

        for ( $n = 1; $n <= $this->cnt_day; $n++ ) {

            // IF not listmode add Prefix Days
            if ( $n == 1 && empty($this->listmode) ) {
                $w = date($_w, mktime(0,0,0,$month,$n,$year));
                for ( ; $_n < intval($w); $_n++ ) {
                    $cnt_week ++;
                    $this->days[$this->getWeekNum($cnt_week)][] = array(); //future: 前月の数値情報?
                }
            }

            /**
             * build Day main Logic
             */
            $date = $year.'-'.$month.'-'.$n;

            if (isset( $this->week_label[ $this->formatW ])) {
                $week_label = $this->week_label[ $this->formatW ][date('w', strtotime($date))];
            } else {
                $week_label = date($this->formatW, strtotime($date));
            }

            $cnt_week ++;
            $this->days[$this->getWeekNum($cnt_week)][] = array(
                'day'       => date($this->formatD, mktime(0,0,0,$month,$n,$year)),
                'id'        => intval($n),
                'week'      => $week_label,
                'weekNo'    => config('schedule_weekNo').date('w', strtotime($date)),
                'timestamp' => date('Y-m-d', mktime(0,0,0,$month,$n,$year)),
                'url'       => acmsLink(array('date' => array($year, $month, $n))),
            );

            // IF not listmode add Surfix Days
            if ($n == $this->cnt_day && empty($this->listmode)) {
                $w = date($_w, mktime(0, 0, 0, $month, $n, $year));
                for ($_n = 0; $_n < intval($_maxN - $w); $_n++) {
                    $cnt_week ++;
                    $this->days[$this->getWeekNum($cnt_week)][] = array(); //future: 次月の数値情報?
                }
            }
        }
    }

    function destructDays()
    {
        $this->days = array();
    }

    function getMonthData($year, $month)
    {
        if ( $this->Post->isExists('reapply') ) {

            $takeover   = $this->Post->getArray('reapply');
            return array('data' => $takeover[0], 'field' => $takeover[1]);

        } else {
            $DB     = DB::singleton(dsn());

            $SQL = SQL::newSelect('schedule');
            $SQL->addSelect('schedule_data');
            $SQL->addSelect('schedule_field');
            $SQL->addWhereOpr('schedule_id', $this->key);
            $SQL->addWhereOpr('schedule_year', $year);
            $SQL->addWhereOpr('schedule_month', $month);
            $SQL->addWhereOpr('schedule_blog_id', $this->bid);
            $row = $DB->query($SQL->get(dsn()), 'row');

            $res = array(
                'data' => false,
                'field' => false,
            );
            if (isset($row['schedule_data'])) {
                $res['data'] = @unserialize($row['schedule_data']);
            }
            if (isset($row['schedule_field'])) {
                $res['field'] = @unserialize($row['schedule_field']);
            }
            return $res;
        }
    }

    function buildMonth(&$Tpl, $month, $year)
    {
        $this->buildDays($year, $month);
        $DATA = $this->getMonthData($year, $month);

        foreach($this->days as $weekKey => $weekRow){
            foreach ( $weekRow as $day ) {
                $data = (isset($day['id']) && isset($DATA['data'][$day['id']])) ? $DATA['data'][$day['id']] : false;
                $field = (isset($day['id']) && isset($DATA['field'][$day['id']])) ? $DATA['field'][$day['id']] : false;

                if( !empty($this->listmode) ) { //リストモードであればここでplan
                    $this->buildPlan($Tpl, $day, $data);
                } elseif( !empty($day) ) {
                    $this->addLabel($Tpl, $day, $data);
                }

                $dayBlock = array('day:loop','week:loop','month:loop','unit:loop');

                if (isset($day['id']) && isset($DATA['field'][$day['id']]) && !empty($DATA['field'][$day['id']])) { //fieldが存在すればadd
                    $vars = $this->buildField($field, $Tpl, $dayBlock, null);
                    $day  = array_merge($day, $vars);
                }
                if ( isset($day['timestamp']) ) {
                    $day    += $this->buildDate($day['timestamp'], $Tpl, 'day:loop');
                }

                $Tpl->add($dayBlock, $day);
                if ( !empty($this->listmode) ) { //リストモードであればここでweek
                    $Tpl->add(array('week:loop','month:loop','unit:loop'), array('weekRowNo' =>$weekKey));
                }
            }

            if ( empty($this->listmode) ) { //リストモードでなければここでweek
                $Tpl->add(array('week:loop','month:loop','unit:loop'), array('weekRowNo' =>$weekKey));
            }
        }

        // 月表示ならば、月送りを出力する( monthly block )
        $ZENGO = $this->getContext($year, $month);
        if ( $this->viewmode == 'month' ) {
            $nextUrl    = explode('-', $ZENGO['nextM']);
            $prevUrl    = explode('-', $ZENGO['prevM']);
            $Tpl->add(array('monthly', 'month:loop', 'unit:loop'), array(
                'year'    => date($this->formatY, mktime(0,0,0,$month,1,$year)),
                'month'   => date($this->formatM, mktime(0,0,0,$month,1,$year)),
                'time'    => date('Y-m-d', mktime(0,0,0,$month,1,$year)),
                'nextUrl' => acmsLink(array('date' => array($nextUrl[0],$nextUrl[1]))),
                'prevUrl' => acmsLink(array('date' => array($prevUrl[0],$prevUrl[1]))),
            ));
        }

        $vars    = array(
            'year'   => date($this->formatY, mktime(0,0,0,$month,1,$year)),
            'month'  => date($this->formatM, mktime(0,0,0,$month,1,$year)),
            'time'   => date('Y-m-d', mktime(0,0,0,$month,1,$year)),
            '_year'  => $year,
            '_month' => $month,
            'cnt_day'=> $this->cnt_day,
            'url'    => acmsLink(array('date' => array($year, $month))),
            'mode'   => $this->listmode ? 'list' : 'grid',
            'next'   => $ZENGO['nextM'],
            'prev'   => $ZENGO['prevM'],
        );

        if ( isset($this->week_label[$this->formatW]) ) {
            for ( $i = 0; $i < 7; $i ++ ) {
                $vars += array('w#'.$i => $this->week_label[$this->formatW][$i]);
            }
        }
        else {
            for ( $i = 0; $i < 7; $i ++ ) {
                $vars += array('w#'.$i => date($this->formatW, strtotime('+'.$i.'day', strtotime('-'.date("w").'day'))));
            }
        }

        // yead select
        $year       = $vars['year'];
        $maxYear    = intval($year) + 10;
        for ( $y=2009; $y<$maxYear; $y++ ) {
            $sYear = array('sYear' => $y);
            if ( $y == $year ) $sYear['selected'] = config('attr_selected');
            $Tpl->add(array('yearSelect:loop', 'month:loop','unit:loop'), $sYear);
        }

        // month select
        $month       = $vars['month'];
        for ( $m=1; $m<=12; $m++ ) {
            $sMonth = array('sMonth' => $m);
            if ( $m == $month ) $sMonth['selected'] = config('attr_selected');
            $Tpl->add(array('monthSelect:loop', 'month:loop','unit:loop'), $sMonth);
        }


        // add Templete
        $Tpl->add(array('month:loop','unit:loop'), $vars);
        $this->destructDays();

        $this->_unit ++;
        if ( $this->_unit == $this->unit ) {
            $Tpl->add('unit:loop');
            $this->_unit = 0;
        }
    }

    function addLabel(&$Tpl, &$day, $Plan)
    {
        $dayNum = $day['id'];
        if (isset($Plan['item'.$dayNum]) && is_array($Plan['item'.$dayNum]) ) {
            // first Label on Calendar
            $labelKey = isset($Plan['label'.$dayNum][0]) ? $Plan['label'.$dayNum][0] : '';

            // first Item on Calendar
            if ( !empty($Plan['item'.$dayNum][0]) ) {
                $day['dayItem'] = $Plan['item'.$dayNum][0];
            }
        }

        if (!empty($labelKey)){
            foreach ( $this->labels as $chunk ) {
                $chunk   = explode($this->sep, $chunk);
                $key     = isset($chunk[1]) ? $chunk[1] : '';
                $label   = isset($chunk[0]) ? $chunk[0] : '';
                $class   = isset($chunk[2]) ? $chunk[2] : '';
                $vars = array(
                    'label' => $label,
                    'key'   => $key,
                    'class' => $class,
                );

                if ($key == $labelKey && !empty($class)) {
                    $day['dayClass'] = $class;
                }
                if ($key == $labelKey && !empty($label)) {
                    $day['dayLabel'] = $label;
                }
            }
        }
    }

    function buildPlan(&$Tpl, $day, $Plan)
    {
        $dayNum = $day['id'];
        $loop = 0;
        $loop2 = 0;
        if (isset($Plan['item'.$dayNum]) && is_array($Plan['item'.$dayNum])) {
            $loop = count($Plan['item'.$dayNum]);
        }
        if (isset($Plan['label'.$dayNum]) && is_array($Plan['label'.$dayNum])) {
            $loop2 = count($Plan['label'.$dayNum]);
        }
        $loop = ($loop < $loop2) ? $loop2 : $loop;
        $cnt = 1;

        /**
         * IF !!ADMIN adjust multiple plan
         */
        if ( !!ADMIN ) {
            if ($loop == 0) $loop = 1;
            else $cnt = 0;
            if (property_exists($this, 'plan') && $this->plan === 'on') {
                $loop += 1;
            } else {
                $loop = 1;
            }
        }
        if (empty($loop)) $loop = 1;

        for ( $i = 0; $i < $loop; $i++ ) {
            $labelKey = isset($Plan['label'.$dayNum][$i]) ? $Plan['label'.$dayNum][$i] : '';
            $planName = isset($Plan['item'.$dayNum][$i]) ? $Plan['item'.$dayNum][$i] : '';
            $planBlock = array('plan', 'day:loop', 'week:loop', 'month:loop', 'unit:loop');
            $planRow = array('no' => $dayNum);
            if ( !empty($planName) ) $planRow += array('item' => $planName);
            if ( !empty($labelKey) ) $planRow += array('key' => $labelKey);
            if ( !empty($this->labels) ) {
                foreach ( $this->labels as $chunk ) {
                    $chunk  = explode($this->sep, $chunk);
                    $key    = isset($chunk[1]) ? $chunk[1] : '';
                    $label  = isset($chunk[0]) ? $chunk[0] : '';
                    $class  = isset($chunk[2]) ? $chunk[2] : '';

                    $vars   = array(
                        'label' => $label,
                        'key'   => $key,
                        'class' => @$class,
                    );

                    $label_inner_vars   = $vars;
                    $label_outer_vars   = $vars;

                    /**
                     * IF !!ADMIN add label:loop / ELSE merge Plan row
                     */
                    if ( !!ADMIN ) {
                        if ( $key == @$labelKey && !($i == ($loop - $cnt)) ) {
                            $label_inner_vars['selected'] = config('attr_selected');
                        } else {
                            unset($label_inner_vars['selected']);
                            unset($planRow['selected']);
                        }

                        $Tpl->add(array('label:loop', 'plan', 'day:loop','week:loop','month:loop','unit:loop'), $label_inner_vars);
                    }

                    if ( $key == @$labelKey ) $planRow += $label_outer_vars;
                }
            }

            /**
             * IF VAR IS EMPTY add var:null
             */
            if ( empty($planRow['item']) )  $Tpl->add(array('item:null', 'plan', 'day:loop', 'week:loop', 'month:loop', 'unit:loop'));
            if ( empty($planRow['label']) ) $Tpl->add(array('label:null', 'plan', 'day:loop', 'week:loop', 'month:loop', 'unit:loop'));
            $Tpl->add($planBlock, $planRow);
        }
    }

    function yearView(&$Tpl)
    {
        for ( $i = 1; $i < 13 ; $i++ ) {
            $this->buildMonth($Tpl, $i, $this->year);
        }

        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('schedule');
        $SQL->addSelect('schedule_year');
        $SQL->addWhereOpr('schedule_id', $this->key);
        $SQL->addWhereOpr('schedule_year', '0000', '<>');
        $SQL->addWhereOpr('schedule_blog_id', $this->bid);

        $Recently   = clone $SQL;
        $Recently->addOrder('schedule_year', 'DESC');
        $recentryValue = $DB->query($SQL->get(dsn()), 'one');

        $Past       = clone $SQL;
        $Past->addOrder('schedule_year', 'ASC');
        $pastValue  = $DB->query($SQL->get(dsn()), 'one');

        $vars = array();

        //-----------
        // prev link
        if ( ($this->year - 1) >= intval($pastValue) ) {
            $vars['prevYear']   = acmsLink(array('date' => array($this->year - 1)));
        }

        //-----------
        // next link
        if ( ($this->year + 1) <= intval($recentryValue) ) {
            $vars['nextYear']   = acmsLink(array('date' => array($this->year + 1)));
        }
        $Tpl->add('year', $vars);
    }

    function monthView(&$Tpl)
    {
        $loop   = $this->forwardM + $this->backM + 1;
        $_year  = date('Y', mktime(0,0,0,$this->month - $this->backM, $this->day, $this->year));
        $_month = date('m', mktime(0,0,0,$this->month - $this->backM, $this->day, $this->year));

        for ( $i = 0; $i < $loop ; $i++ ) {
            $year   = date('Y', mktime(0,0,0,$_month+$i, $this->day, $_year));
            $month  = date('m', mktime(0,0,0,$_month+$i, $this->day, $_year));
            $this->buildMonth($Tpl, $month, $year);
        }

        if ( fmod($loop, $this->unit) && $loop != 1 ) {
            $Tpl->add('unit:loop');
        }
    }

    function daysView(&$Tpl)
    {
        $loop   = $this->forwardD + $this->backD + 1;

        $_year  = date('Y', mktime(0,0,0,$this->month, $this->day - $this->backD, $this->year));
        $_month = date('m', mktime(0,0,0,$this->month, $this->day - $this->backD, $this->year));
        $_day   = date('d', mktime(0,0,0,$this->month, $this->day - $this->backD, $this->year));

        $now    = mktime(0,0,0,$_month,$_day,$_year);

        for ( $i = 0; $i < $loop; $i++ ) {
            $ymd    = date('Y-m-j', strtotime('+'.$i.'day', $now));
            $ymd    = explode('-', $ymd);

            $year   = $ymd[0];
            $month  = $ymd[1];
            $dayNum = $ymd[2];

            // sotre data
            if ( empty($DATA[$year.$month]) )
            {
                $DATA[$year.$month] = $this->getMonthData($year, $month);
            }

            // store days
            if ( empty($DAYS[$year.$month]) )
            {
                $this->buildDays($year, $month);
                $DAYS[$year.$month][0] = '';
                foreach ( $this->days as $weekRow ) {
                    $DAYS[$year.$month] = array_merge($DAYS[$year.$month], $weekRow);
                }
                $this->destructDays();
            }

            $day = null;
            $plan = null;
            $field = null;

            if (isset($DAYS[$year.$month])) {
                if (isset($DAYS[$year.$month][$dayNum])) {
                    $day = $DAYS[$year.$month][$dayNum];
                }
                if (isset($DATA[$year.$month]['data']) && isset($DATA[$year.$month]['data'][$dayNum])) {
                    $plan = $DATA[$year.$month]['data'][$dayNum];
                }
                if (isset($DATA[$year.$month]['field']) && isset($DATA[$year.$month]['field'][$dayNum])) {
                    $field = $DATA[$year.$month]['field'][$dayNum];
                }
            }

            $this->buildPlan($Tpl, $day, $plan);
            $this->addLabel($Tpl, $day, $plan);

            $dayBlock = array('day:loop','week:loop','month:loop','unit:loop');

            if ( !empty($field) ) { //fieldが存在すればadd
                $vars = $this->buildField($field, $Tpl, $dayBlock, null);
                $day  = array_merge($day, $vars);
            }

            $Tpl->add($dayBlock, array_merge($day, array(
                                            'year'   => date($this->formatY, mktime(0,0,0,$month,1,$year)),
                                            'month'  => date($this->formatM, mktime(0,0,0,$month,1,$year)),
                                            'time'   => date('Y-m-d', mktime(0,0,0,$month,1,$year)),
                                                        )));

            $Tpl->add(array('week:loop','month:loop','unit:loop'));
        }

        $vars    = array(

                        );

        $Tpl->add(array('month:loop','unit:loop'), $vars);
    }

    function getContext($year, $month, $day = null)
    {
        if ( empty($day) ) $day = 1;
        $fmt   = 'Y-m-d';
        $ZENGO = array(
            'nextM' => date($fmt, mktime(0,0,0,$month + 1, $day, $year)),
            'prevM' => date($fmt, mktime(0,0,0,$month - 1, $day, $year)),
            'nextY' => date($fmt, mktime(0,0,0,$month, $day, $year + 1)),
            'prevY' => date($fmt, mktime(0,0,0,$month, $day, $year - 1)),
            'nextD' => date($fmt, mktime(0,0,0,$month, $day + 1, $year)),
            'prevD' => date($fmt, mktime(0,0,0,$month, $day - 1, $year)),
        );
        return $ZENGO;
    }
}
