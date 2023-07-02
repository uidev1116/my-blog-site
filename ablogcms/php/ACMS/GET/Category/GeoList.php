<?php

class ACMS_GET_Category_GeoList extends ACMS_GET
{
    var $_scope = array(
        'cid' => 'global',
    );

    /**
     * 緯度
     *
     * @var float
     */
    protected $lat;

    /**
     * 経度
     *
     * @var float
     */
    protected $lng;

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
    protected $categories = array();

    /**
     * @return array
     */
    protected function initVars()
    {
        return array(
            'referencePoint' => config('category_geo-list_reference_point'),
            'within'  => floatval(config('category_geo-list_within')),
            'limit' => intval(config('category_geo-list_limit')),
            'loop_class' => config('category_geo-list_loop_class'),
        );
    }

    /**
     * @return mixed
     */
    function get()
    {
        if ( !$this->setConfig() ) return '';
        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        $this->getReferencePoint();

        if ( 1
            && $this->config['referencePoint'] === 'url_query_string'
            && (!$this->lat || !$this->lng)
        ) {
            $Tpl->add('notFoundGeolocation');
            return $Tpl->get();
        }
        if ( !$this->lat || !$this->lng ) {
            return '';
        }

        $DB = DB::singleton(dsn());
        $this->buildModuleField($Tpl);

        $SQL = $this->buildQuery();
        $this->categories = $DB->query($SQL->get(dsn()), 'all');
        $this->build($Tpl);

        $currentCategory = loadCategoryField($this->cid);
        $currentCategory->overload(loadCategory($this->cid));
        $currentCategory->set('url', acmsLink(array(
            'bid' => $this->bid,
            'cid' => $this->cid,
        )));
        $Tpl->add('currentCategory', $this->buildField($currentCategory, $Tpl));

        return $Tpl->get();
    }

    /**
     * 絞り込みクエリ組み立て
     *
     * @param SQL_Select & $SQL
     * @return void
     */
    protected function filterQuery(& $SQL)
    {
        $SQL->addWhereOpr('category_parent', $this->cid);
        $SQL->addWhereOpr('blog_indexing', 'on');
        ACMS_Filter::categoryStatus($SQL);
        if ( !empty($this->keyword) ) {
            ACMS_Filter::categoryKeyword($SQL, $this->keyword);
        }
        if ( !empty($this->Field) ) {
            ACMS_Filter::categoryField($SQL, $this->Field);
        }
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

        $j = count($this->categories) - 1;
        foreach ($this->categories as $i => $row) {
            $cid = intval($row['category_id']);

            //-------
            // field
            $Field = loadCategoryField($cid);
            foreach ( $row as $key => $val ) {
                $Field->setField(preg_replace('/category\_/', '', $key), $val);
            }
            $Field->set('url', acmsLink(array(
                'bid' => $row['category_blog_id'],
                'cid' => $cid,
            )));
            $Field->set('category:loop.class', $loopClass);

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
            $Tpl->add('category:loop', $vars);
        }
    }


    /**
     * 基準点となる位置情報を取得
     *
     * @return void
     */
    protected function getReferencePoint()
    {
        if ( $this->config['referencePoint'] === 'url_context' && $this->cid ) {
            $DB = DB::singleton(dsn());
            $SQL = SQL::newSelect('geo', 'geo');
            $SQL->addSelect('geo_geometry', 'lat', 'geo', POINT_Y);
            $SQL->addSelect('geo_geometry', 'lng', 'geo', POINT_X);
            $SQL->addWhereOpr('geo_cid', $this->cid);
            if ( $data = $DB->query($SQL->get(dsn()), 'row') ) {
                $this->lat = $data['lat'];
                $this->lng = $data['lng'];
            }
        } else if ( $this->config['referencePoint'] === 'url_query_string' ) {
            $this->lat = $this->Get->get('lat');
            $this->lng = $this->Get->get('lng');
        }
    }

    /**
     * sqlの組み立て
     *
     * @return SQL_Select
     */
    function buildQuery()
    {
        $SQL = SQL::newSelect('geo');
        $SQL->addLeftJoin('category', 'category_id', 'geo_cid');
        $SQL->addLeftJoin('blog', 'blog_id', 'category_blog_id');
        $SQL->addSelect('*');
        $SQL->addSelect('geo_geometry', 'longitude', null, POINT_X);
        $SQL->addSelect('geo_geometry', 'latitude', null, POINT_Y);
        $SQL->addGeoDistance('geo_geometry', $this->lng, $this->lat, 'distance');

        if ( $this->config['referencePoint'] === 'url_context' && $this->bid ) {
            $SQL->addWhereOpr('geo_cid', $this->cid, '<>');
        }
        $within = $this->config['within'];
        if ( $within > 0 ) {
            $within = $within * 1000;
            $SQL->addHaving('distance < '.$within);
        }

        $this->filterQuery($SQL);
        $SQL->addOrder('distance', 'ASC');
        $this->limitQuery($SQL);
        $SQL->setGroup('category_id');

        return $SQL;
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