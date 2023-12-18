<?php

class ACMS_GET_Entry_GeoList extends ACMS_GET_Entry_Summary
{
    var $_axis = array(
        'bid' => 'self',
        'cid' => 'self',
    );

    var $_scope = array(
        'eid' => 'global',
    );

    protected $lat;
    protected $lng;

    /**
     * コンフィグの取得
     *
     * @return array
     */
    function initVars()
    {
        return array(
            'referencePoint'        => config('entry_geo-list_reference_point'),
            'within'                => floatval(config('entry_geo-list_within')),

            'order'                 => null,
            'limit'                 => intval(config('entry_geo-list_limit')),
            'offset'                => intval(config('entry_geo-list_offset')),
            'indexing'              => config('entry_geo-list_indexing'),
            'membersOnly'           => config('entry_geo-list_members_only'),
            'secret'                => config('entry_geo-list_secret'),
            'notfound'              => config('mo_entry_geo-list_notfound'),
            'notfoundStatus404'     => config('entry_geo-list_notfound_status_404'),
            'noimage'               => config('entry_geo-list_noimage'),
            'pagerDelta'            => config('entry_geo-list_pager_delta'),
            'pagerCurAttr'          => config('entry_geo-list_pager_cur_attr'),

            'unit'                  => config('entry_geo-list_unit'),
            'newtime'               => config('entry_geo-list_newtime'),
            'imageX'                => intval(config('entry_geo-list_image_x')),
            'imageY'                => intval(config('entry_geo-list_image_y')),
            'imageTrim'             => config('entry_geo-list_image_trim'),
            'imageZoom'             => config('entry_geo-list_image_zoom'),
            'imageCenter'           => config('entry_geo-list_image_center'),

            'entryFieldOn'          => config('entry_geo-list_entry_field'),
            'categoryInfoOn'        => config('entry_geo-list_category_on'),
            'categoryFieldOn'       => config('entry_geo-list_category_field_on'),
            'userInfoOn'            => config('entry_geo-list_user_on'),
            'userFieldOn'           => config('entry_geo-list_user_field_on'),
            'blogInfoOn'            => config('entry_geo-list_blog_on'),
            'blogFieldOn'           => config('entry_geo-list_blog_field_on'),
            'pagerOn'               => config('entry_geo-list_pager_on'),
            'simplePagerOn'         => config('entry_geo-list_simple_pager_on'),
            'mainImageOn'           => config('entry_geo-list_image_on'),
            'detailDateOn'          => config('entry_geo-list_date'),
            'fullTextOn'            => config('entry_geo-list_fulltext'),
            'fulltextWidth'         => config('entry_geo-list_fulltext_width'),
            'fulltextMarker'        => config('entry_geo-list_fulltext_marker'),
            'tagOn'                 => config('entry_geo-list_tag'),
            'hiddenCurrentEntry'    => config('entry_geo-list_hidden_current_entry'),
            'hiddenPrivateEntry'    => config('entry_geo-list_hidden_private_entry'),
            'loop_class'            => config('entry_geo-list_loop_class'),
        );
    }

    /**
     * 起動
     *
     * @return string
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
            if ( $this->buildNotFound($Tpl) ) {
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
    function getReferencePoint()
    {
        if ( $this->config['referencePoint'] === 'url_context' && $this->eid ) {
            $DB = DB::singleton(dsn());
            $SQL = SQL::newSelect('geo', 'geo');
            $SQL->addSelect('geo_geometry', 'lat', 'geo', POINT_Y);
            $SQL->addSelect('geo_geometry', 'lng', 'geo', POINT_X);
            $SQL->addWhereOpr('geo_eid', $this->eid);
            $SQL->addWhereOpr('geo_blog_id', BID);
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
        $within = $this->config['within'];

        $SQL = SQL::newSelect('geo');

        $SQL->addLeftJoin('entry', 'entry_id', 'geo_eid');
        $SQL->addLeftJoin('blog', 'blog_id', 'entry_blog_id');
        $SQL->addLeftJoin('category', 'category_id', 'entry_category_id');

        $SQL->addSelect('*');
        $SQL->addSelect('geo_geometry', 'longitude', null, POINT_X);
        $SQL->addSelect('geo_geometry', 'latitude', null, POINT_Y);
        $SQL->addGeoDistance('geo_geometry', $this->lng, $this->lat, 'distance');

        if ( $this->config['referencePoint'] === 'url_context' && $this->eid ) {
            $SQL->addWhereOpr('geo_eid', $this->eid, '<>');
        }

        if ( $within > 0 ) {
            $within = $within * 1000;
            $SQL->addHaving('distance < '.$within);
        }

        $this->filterQuery($SQL);
        $this->setAmount($SQL);
        $SQL->addOrder('distance', 'ASC');
        $this->limitQuery($SQL);

        return $SQL->get(dsn());
    }

    /**
     * エントリー数取得sqlの準備
     *
     * @param SQL_Select $SQL
     * @return void
     */
    function setAmount($SQL)
    {
        $temp = clone $SQL;
        $temp->setSelect('entry_id');
        $temp->addGeoDistance('geo_geometry', $this->lng, $this->lat, 'distance');

        $this->amount = SQL::newSelect($temp, 'count');
        $this->amount->setSelect('DISTINCT(entry_id)', 'entry_amount', null, 'COUNT');
    }

    /**
     * エントリーの絞り込み
     *
     * @param SQL_Select & $SQL
     * @return bool
     */
    function entryFilterQuery(& $SQL)
    {
        return false;
    }

    /**
     * @return array
     */
    protected function dsn()
    {
        return dsn();
    }
}
