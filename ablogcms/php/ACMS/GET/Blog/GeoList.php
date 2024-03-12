<?php

class ACMS_GET_Blog_GeoList extends ACMS_GET_Blog_ChildList
{
    public $_scope = array(
        'bid' => 'global',
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
     * @return array
     */
    protected function initVars()
    {
        return array(
            'referencePoint' => config('blog_geo-list_reference_point'),
            'within'  => floatval(config('blog_geo-list_within')),
            'order' => config('blog_geo-list_order'),
            'limit' => intval(config('blog_geo-list_limit')),
            'loop_class' => config('blog_geo-list_loop_class'),
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
        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        $this->getReferencePoint();

        if (
            1
            && $this->config['referencePoint'] === 'url_query_string'
            && (!$this->lat || !$this->lng)
        ) {
            $Tpl->add('notFoundGeolocation');
            return $Tpl->get();
        }
        if (!$this->lat || !$this->lng) {
            return '';
        }
        return parent::get();
    }

    /**
     * 基準点となる位置情報を取得
     *
     * @return void
     */
    protected function getReferencePoint()
    {
        if ($this->config['referencePoint'] === 'url_context' && $this->bid) {
            $DB = DB::singleton(dsn());
            $SQL = SQL::newSelect('geo', 'geo');
            $SQL->addSelect('geo_geometry', 'lat', 'geo', POINT_Y);
            $SQL->addSelect('geo_geometry', 'lng', 'geo', POINT_X);
            $SQL->addWhereOpr('geo_bid', $this->bid);
            if ($data = $DB->query($SQL->get(dsn()), 'row')) {
                $this->lat = $data['lat'];
                $this->lng = $data['lng'];
            }
        } elseif ($this->config['referencePoint'] === 'url_query_string') {
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
        $SQL->addLeftJoin('blog', 'blog_id', 'geo_bid');
        $SQL->addSelect('*');
        $SQL->addSelect('geo_geometry', 'longitude', null, POINT_X);
        $SQL->addSelect('geo_geometry', 'latitude', null, POINT_Y);
        $SQL->addGeoDistance('geo_geometry', $this->lng, $this->lat, 'distance');

        if ($this->config['referencePoint'] === 'url_context' && $this->bid) {
            $SQL->addWhereOpr('geo_bid', $this->bid, '<>');
        }
        $within = $this->config['within'];
        if ($within > 0) {
            $within = $within * 1000;
            $SQL->addHaving('distance < ' . $within);
        }

        $this->filterQuery($SQL);
        $SQL->addOrder('distance', 'ASC');
        $this->limitQuery($SQL);
        $SQL->setGroup('blog_id');

        return $SQL;
    }
}
