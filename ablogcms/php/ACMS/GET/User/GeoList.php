<?php

class ACMS_GET_User_GeoList extends ACMS_GET_User_Search
{
    public $_axis = array(
        'bid' => 'self',
        'cid' => 'self',
    );

    public $_scope = array(
        'uid' => 'global',
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
     * コンフィグの取得
     *
     * @return array
     */
    protected function initVars()
    {
        return array(
            'referencePoint' => config('user_geo-list_reference_point'),
            'within'  => floatval(config('user_geo-list_within')),
            'indexing' => config('user_geo-list_indexing'),
            'auth' => configArray('user_geo-list_auth'),
            'status' => configArray('user_geo-list_status'),
            'mail_magazine' => configArray('user_geo-list_mail_magazine'),
            'limit' => intval(config('user_geo-list_limit')),
            'loop_class' => config('user_geo-list_loop_class'),
            'pager_delta' => config('user_geo-list_pager_delta'),
            'pager_cur_attr' => config('user_geo-list_pager_cur_attr'),
            'entry_list_enable' => config('user_geo-list_entry_list_enable'),
            'entry_list_order' => config('user_geo-list_entry_list_order'),
            'entry_list_limit' => config('user_geo-list_entry_list_limit'),
        );
    }

    /**
     * Run
     *
     * @return string
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
            if ($this->buildNotFound($Tpl)) {
                return $Tpl->get();
            } else {
                return '';
            }
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
        if ($this->config['referencePoint'] === 'url_context' && $this->uid) {
            $DB = DB::singleton(dsn());
            $SQL = SQL::newSelect('geo', 'geo');
            $SQL->addSelect('geo_geometry', 'lat', 'geo', POINT_Y);
            $SQL->addSelect('geo_geometry', 'lng', 'geo', POINT_X);
            $SQL->addWhereOpr('geo_uid', $this->uid);
            $SQL->addWhereOpr('geo_blog_id', BID);
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
        $SQL->addLeftJoin('user', 'user_id', 'geo_uid');
        $SQL->addLeftJoin('blog', 'blog_id', 'user_blog_id');
        $SQL->addSelect('*');
        $SQL->addSelect('geo_geometry', 'longitude', null, POINT_X);
        $SQL->addSelect('geo_geometry', 'latitude', null, POINT_Y);
        $SQL->addGeoDistance('geo_geometry', $this->lng, $this->lat, 'distance');

        if ($this->config['referencePoint'] === 'url_context' && $this->uid) {
            $SQL->addWhereOpr('geo_uid', $this->uid, '<>');
        }
        $within = $this->config['within'];
        if ($within > 0) {
            $within = $within * 1000;
            $SQL->addHaving('distance < ' . $within);
        }

        $this->filterQuery($SQL);
        $this->setAmount($SQL);
        $SQL->addOrder('distance', 'ASC');
        $this->limitQuery($SQL);

        return $SQL;
    }

    /**
     * ユーザー数取得sqlの準備
     *
     * @param SQL_Select $SQL
     * @return void
     */
    protected function setAmount($SQL)
    {
        $this->amount = SQL::newSelect($SQL, 'amount');
        $this->amount->setSelect('DISTINCT(user_id)', 'user_amount', null, 'COUNT');
    }

    /**
     * limitクエリ組み立て
     *
     * @param SQL_Select & $SQL
     * @return void
     */
    protected function limitQuery(&$SQL)
    {
        $limit = $this->config['limit'];
        $from = ($this->page - 1) * $limit;
        $SQL->setLimit($limit, $from);
    }
}
