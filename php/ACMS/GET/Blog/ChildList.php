<?php

class ACMS_GET_Blog_ChildList extends ACMS_GET
{
    var $_scope = array(
        'bid'   => 'global',
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
    protected $blog = array();

    /**
     * @return array
     */
    protected function initVars()
    {
        return array(
            'order' => config('blog_child_list_order'),
            'limit' => intval(config('blog_child_list_limit')),
            'loop_class' => config('blog_child_list_loop_class'),
        );
    }

    /**
     * @return mixed
     */
    function get()
    {
        if (!$this->setConfig()) {
            return '';
        }

        $DB = DB::singleton(dsn());
        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        $this->buildModuleField($Tpl);

        $SQL = $this->buildQuery();
        $this->blog = $DB->query($SQL->get(dsn()), 'all');
        $this->build($Tpl);

        $currentBlog = loadBlogField($this->bid);
        $currentBlog->overload(loadBlog($this->bid));
        $currentBlog->set('url', acmsLink(array(
            'bid'   => $this->bid,
        )));
        $Tpl->add('currentBlog', $this->buildField($currentBlog, $Tpl));

        return $Tpl->get();
    }

    /**
     * @return SQL_Select
     */
    protected function buildQuery()
    {
        $SQL = SQL::newSelect('blog');

        if (config('blog_child_list_geolocation_on') === 'on') {
            $SQL->addLeftJoin('geo', 'geo_bid', 'blog_id');
            $SQL->addSelect('*');
            $SQL->addSelect('geo_geometry', 'longitude', null, POINT_X);
            $SQL->addSelect('geo_geometry', 'latitude', null, POINT_Y);
        }
        $this->filterQuery($SQL);
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
    protected function filterQuery(& $SQL)
    {
        $SQL->addWhereOpr('blog_parent', $this->bid);
        $SQL->addWhereOpr('blog_indexing', 'on');
        ACMS_Filter::blogStatus($SQL);
        if ( !empty($this->keyword) ) {
            ACMS_Filter::blogKeyword($SQL, $this->keyword);
        }
        if ( !empty($this->Field) ) {
            ACMS_Filter::blogField($SQL, $this->Field);
        }
    }

    /**
     * orderクエリ組み立て
     *
     * @param SQL_Select & $SQL
     * @return void
     */
    protected function orderQuery(& $SQL)
    {
        ACMS_Filter::blogOrder($SQL, $this->config['order']);
    }

    /**
     * limitクエリ組み立て
     *
     * @param SQL_Select & $SQL
     * @return void
     */
    protected function limitQuery(& $SQL)
    {
        $SQL->setLimit($this->config['limit']);
    }

    /**
     * テンプレートの組み立て
     *
     * @param $Tpl
     */
    protected function build(& $Tpl)
    {
        $loopClass = $this->config['loop_class'];

        $j = count($this->blog) - 1;
        foreach ($this->blog as $i => $row) {
            $bid = intval($row['blog_id']);

            //-------
            // field
            $Field  = loadBlogField($bid);
            foreach ( $row as $key => $val ) {
                $Field->setField(preg_replace('/blog\_/', '', $key), $val);
            }
            $Field->set('url', acmsLink(array(
                'bid'   => $bid,
            )));
            $Field->set('blog:loop.class', $loopClass);

            //------
            // glue
            if ( $i !== $j ) {
                $Tpl->add('glue');
            }
            $vars = $this->buildField($Field, $Tpl);
            if (isset($row['distance'])) {
                $vars['geo_distance'] = $row['distance'];
            }
            if (isset($row['latitude'])) {
                $vars['geo_lat'] = $row['latitude'];
            }
            if (isset($row['longitude'])) {
                $vars['geo_lng'] = $row['longitude'];
            }
            $Tpl->add('blog:loop', $vars);
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
}
