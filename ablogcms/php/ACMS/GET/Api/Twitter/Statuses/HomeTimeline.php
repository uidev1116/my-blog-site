<?php

class ACMS_GET_Api_Twitter_Statuses_HomeTimeline extends ACMS_GET_Api_Twitter_Statuses
{
    var $ignore;

    var $_scope = array(
        'field'     => 'global',
    );

    function get()
    {
        $this->limit  = !!LIMIT ? LIMIT : config('twitter_statuses_hometl_limit');
        $this->ignore = config('twitter_statuses_hometl_private');
        $this->id     = $this->bid;
        $this->api    = 'statuses/home_timeline.json';
        $this->params = array_clean(array(
            'since_id'  => ($since_id = $this->Field->get('since_id')) ? intval($since_id) : null,
            'max_id'    => ($max_id   = $this->Field->get('max_id'))   ? intval($max_id)   : null,
            'count'     => intval($this->limit),
        ));
        $this->crit = config('twitter_statuses_hometl_cache_expire');

        return $this->statuses();
    }
}
