<?php

class ACMS_GET_User_Search extends ACMS_GET
{
    public $_scope = array(
        'uid' => 'global',
        'field' => 'global',
        'page' => 'global',
    );

    /**
     * @var array
     */
    protected $config;

    /**
     * @var SQL_Select
     */
    protected $amount;

    /**
     * @var array
     */
    protected $users;

    /**
     * @return array
     */
    protected function initVars()
    {
        return array(
            'indexing' => config('user_search_indexing'),
            'auth' => configArray('user_search_auth'),
            'status' => configArray('user_search_status'),
            'mail_magazine' => configArray('user_search_mail_magazine'),
            'order' => config('user_search_order'),
            'limit' => intval(config('user_search_limit')),
            'loop_class' => config('user_search_loop_class'),
            'pager_delta' => config('user_search_pager_delta'),
            'pager_cur_attr' => config('user_search_pager_cur_attr'),
            'entry_list_enable' => config('user_search_entry_list_enable'),
            'entry_list_order' => config('user_search_entry_list_order'),
            'entry_list_limit' => config('user_search_entry_list_limit'),
            'geolocation_on' => config('user_search_geolocation_on')
        );
    }

    function get()
    {
        if (!$this->setConfig()) {
            return '';
        }

        $DB = DB::singleton(dsn());
        $Tpl = new Template($this->tpl, new ACMS_Corrector());

        $this->buildModuleField($Tpl);

        $SQL = $this->buildQuery();
        $this->users = $DB->query($SQL->get(dsn()), 'all');

        if ($this->buildNotFound($Tpl)) {
            return $Tpl->get();
        }
        $this->build($Tpl);
        if (empty($this->users)) {
            return '';
        }
        $vars = $this->buildUserPager($Tpl);
        $Tpl->add(null, $vars);

        return $Tpl->get();
    }

    /**
     * ルート変数の取得
     *
     * @param $Tpl
     * @return array
     */
    protected function buildUserPager(&$Tpl)
    {
        if (empty($this->uid)) {
            $itemsAmount = intval(DB::query($this->amount->get(dsn()), 'one'));
            return $this->buildPager(
                $this->page,
                $this->config['limit'],
                $itemsAmount,
                $this->config['pager_delta'],
                $this->config['pager_cur_attr'],
                $Tpl
            );
        }
        return array();
    }

    /**
     * テンプレートの組み立て
     *
     * @param $Tpl
     */
    protected function build(&$Tpl)
    {
        // entry list config
        $entry_list_enable = $this->config['entry_list_enable'] === 'on';
        $loop_class = $this->config['loop_class'];

        //-----------
        // user:loop
        foreach ($this->users as $i => $row) {
            unset($row['user_pass']);
            unset($row['user_pass_reset']);
            unset($row['user_generated_datetime']);

            $vars = $this->buildField(loadUserField(intval($row['user_id'])), $Tpl);
            $vars['i'] = $i;
            foreach ($row as $key => $value) {
                if (strpos($key, 'user_') !== 0) {
                    continue;
                }
                $vars[substr($key, strlen('user_'))] = $value;
            }
            $id = intval($row['user_id']);
            $vars['icon'] = loadUserIcon($id);
            if ($large = loadUserLargeIcon($id)) {
                $vars['largeIcon'] = $large;
            }
            if ($orig = loadUserOriginalIcon($id)) {
                $vars['origIcon'] = $orig;
            }
            if ($entry_list_enable) {
                $this->loadUserEntry($Tpl, $id, array('user:loop'));
            }
            $vars['user:loop.class'] = $loop_class;
            if (!empty($i)) {
                $Tpl->add(array_merge(array('user:glue', 'user:loop')));
            }
            if (isset($row['distance'])) {
                $vars['geo_distance'] = $row['distance'];
            }
            if (isset($row['latitude'])) {
                $vars['geo_lat'] = $row['latitude'];
            }
            if (isset($row['longitude'])) {
                $vars['geo_lng'] = $row['longitude'];
            }
            if (isset($row['geo_zoom'])) {
                $vars['geo_zoom'] = $row['geo_zoom'];
            }
            $Tpl->add('user:loop', $vars);
        }
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
     * @return SQL_Select
     */
    protected function buildQuery()
    {
        $SQL = SQL::newSelect('user');
        $SQL->addLeftJoin('blog', 'blog_id', 'user_blog_id');

        if ($this->config['geolocation_on'] === 'on') {
            $SQL->addLeftJoin('geo', 'geo_uid', 'user_id');
            $SQL->addSelect('*');
            $SQL->addSelect('geo_geometry', 'longitude', null, POINT_X);
            $SQL->addSelect('geo_geometry', 'latitude', null, POINT_Y);
        }

        $this->filterQuery($SQL);
        if ($uid = intval($this->uid)) {
            $SQL->addWhereOpr('user_id', $uid);
        }
        $this->setAmount($SQL); // limitする前のクエリから全件取得のクエリを準備しておく
        $this->orderQuery($SQL);
        $this->limitQuery($SQL);

        return $SQL;
    }


    /**
     * 絞り込みクエリ組み立て
     *
     * @param SQL_Select & $SQL
     * @return void
     */
    protected function filterQuery(&$SQL)
    {
        $SQL->addWhereOpr('user_pass', '', '<>');

        // blog axis
        ACMS_Filter::blogTree($SQL, $this->bid, $this->blogAxis());
        ACMS_Filter::blogStatus($SQL);
        // field
        if (!empty($this->Field)) {
            ACMS_Filter::userField($SQL, $this->Field);
        }
        // keyword
        if (!empty($this->keyword)) {
            ACMS_Filter::userKeyword($SQL, $this->keyword);
        }
        // indexing
        if ($this->config['indexing'] === 'on') {
            $SQL->addWhereOpr('user_indexing', 'on');
        }
        // auth
        if ($this->config['auth']) {
            $SQL->addWhereIn('user_auth', $this->config['auth']);
        }
        // status 2013/02/08
        if ($this->config['status']) {
            $statusWhere = SQL::newWhere();
            foreach ($this->config['status'] as $status) {
                if ($status === 'open') {
                    $openStatusWhere = SQL::newWhere();
                    $openStatusWhere->addWhereOpr('user_login_expire', date('Y-m-d', REQUEST_TIME), '>=', 'AND');
                    $openStatusWhere->addWhereOpr('user_status', 'open', '=', 'AND');
                    $statusWhere->addWhere($openStatusWhere, 'OR');
                } elseif ($status === 'close') {
                    $closeStatusWhere = SQL::newWhere();
                    $closeStatusWhere->addWhereOpr('user_login_expire', date('Y-m-d', REQUEST_TIME), '<', 'OR');
                    $closeStatusWhere->addWhereOpr('user_status', 'close', '=', 'OR');
                    $statusWhere->addWhere($closeStatusWhere, 'OR');
                } else {
                    $otherStatusWhere = SQL::newWhere();
                    $otherStatusWhere->addWhereOpr('user_status', $status, '=', 'OR');
                    $statusWhere->addWhere($otherStatusWhere, 'OR');
                }
            }
            $SQL->addWhere($statusWhere, 'AND');
        }
        // mail_magazine 2013/02/08
        if ($ary_mailmagazine = $this->config['mail_magazine']) {
            if (is_array($ary_mailmagazine) && count($ary_mailmagazine) > 0) {
                foreach ($ary_mailmagazine as $key_mailmagazine => $val_mailmagazine) {
                    switch ($val_mailmagazine) {
                        case 'pc':
                            $SQL->addWhereOpr('user_mail_magazine', 'on');
                            $SQL->addWhereOpr('user_mail', '', '<>');
                            break;
                        case 'mobile':
                            $SQL->addWhereOpr('user_mail_mobile_magazine', 'on');
                            $SQL->addWhereOpr('user_mail_mobile', '', '<>');
                            break;
                    }
                }
            }
        }
    }

    /**
     * ユーザー数取得sqlの準備
     *
     * @param SQL_Select $SQL
     * @return void
     */
    protected function setAmount($SQL)
    {
        $this->amount = new SQL_Select($SQL);
        $this->amount->setSelect('DISTINCT(user_id)', 'user_amount', null, 'COUNT');
    }

    /**
     * orderクエリ組み立て
     *
     * @param SQL_Select & $SQL
     * @return void
     */
    protected function orderQuery(&$SQL)
    {
        if (empty($this->uid)) {
            ACMS_Filter::userOrder($SQL, $this->config['order']);
            $SQL->setGroup('user_id');
        }
    }

    /**
     * limitクエリ組み立て
     *
     * @param SQL_Select & $SQL
     * @return void
     */
    protected function limitQuery(&$SQL)
    {
        if ($this->uid) {
            $SQL->setLimit(1);
        } else {
            $limit = $this->config['limit'];
            $from = ($this->page - 1) * $limit;
            $SQL->setLimit($limit, $from);
        }
    }

    /**
     * NotFound時のテンプレート組み立て
     *
     * @param Template & $Tpl
     * @return bool
     */
    protected function buildNotFound(&$Tpl)
    {
        if (!empty($this->users)) {
            return false;
        }
        $Tpl->add('notFound');
        return true;
    }

    protected function loadUserEntry(&$Tpl, $uid, $block = array())
    {
        $DB = DB::singleton(dsn());

        $SQL = SQL::newSelect('entry');
        $SQL->addWhereOpr('entry_user_id', $uid);
        $SQL->addLeftJoin('blog', 'blog_id', 'entry_blog_id');
        ACMS_Filter::entrySession($SQL);
        ACMS_Filter::entrySpan($SQL, $this->start, $this->end);

        if (!empty($this->bid)) {
            ACMS_Filter::blogTree($SQL, $this->bid, $this->blogAxis());
            ACMS_Filter::blogStatus($SQL);
        }
        ACMS_Filter::entryOrder($SQL, $this->config['entry_list_order'], $uid);
        $SQL->setLimit($this->config['entry_list_limit'], 0);
        $SQL->setGroup('entry_id');
        $q = $SQL->get(dsn());

        $entries = $DB->query($q, 'all');
        foreach ($entries as $i => $entry) {
            $link = $entry['entry_link'];
            $vars = array();
            $url = acmsLink(array(
                'bid' => $entry['entry_blog_id'],
                'cid' => $entry['entry_category_id'],
                'eid' => $entry['entry_id'],
            ));
            if (!empty($i)) {
                $Tpl->add(array_merge(array('glue', 'entry:loop')));
            }
            if (!empty($i)) {
                $Tpl->add(array_merge(array('entry:glue', 'entry:loop')));
            }

            if ($link != '#') {
                $vars += array(
                    'url' => !empty($link) ? $link : $url,
                );
                $Tpl->add(array_merge(array('url#rear', 'entry:loop'), $block));
            }
            $vars['title'] = addPrefixEntryTitle(
                $entry['entry_title'],
                $entry['entry_status'],
                $entry['entry_start_datetime'],
                $entry['entry_end_datetime'],
                $entry['entry_approval']
            );
            $Tpl->add(array_merge(array('entry:loop'), $block), $vars);
        }
    }
}
