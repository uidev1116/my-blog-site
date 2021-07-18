<?php

class ACMS_GET_Api_Twitter_Search extends ACMS_GET_Api_Twitter_Statuses
{
    var $ignore;

    var $_scope = array(
        'field'     => 'global',
        'keyword'   => 'global',
    );

    function get()
    {
        $this->limit  = !!LIMIT ? LIMIT : config('twitter_search_limit');
        $this->ignore = config('twitter_search_private');
        $this->id     = $this->bid;
        $this->api    = 'search/tweets.json';
        $this->params = array_clean(array(
            'since_id'  => ($since_id = $this->Field->get('since_id')) ? intval($since_id) : null,
            'max_id'    => ($max_id   = $this->Field->get('max_id'))   ? intval($max_id)   : null,
            'count'       => intval($this->limit),
            'q'         => $this->keyword,
            'locale'    => ('on' == config('twitter_search_locale') ) ? 'ja' : null,
        ));
        $this->crit = config('twitter_search_cache_expire');

        return $this->statuses();
    }

    // Search APIはJSONかATOMしかレスポンスを取得できない
    function build($response, $Tpl)
    {
        $json = json_decode($response);

        if ( $json === false ) {
            $Tpl->add('unavailable');
            return false;
        }

        if ( count($json->statuses) === 0 ) {
            $Tpl->add('notFound');
            return false;
        }

        $loop  = 0;
        $args  = array();

        foreach ( $json->statuses as $row ) {

            $vars   = array(
                'text'      => $row->text,
                'screen_name' => $row->user->screen_name,
                'user_id'   => $row->user->id_str,
                'status_id' => $row->id_str,
                'image'     => $row->user->profile_image_url,
                'l-image'   => $this->largeImageUrl($row->user->profile_image_url),
                'permalink' => ACMS_GET_Api_Twitter::WEB_URL.$row->user->screen_name.'/status/'.$row->id_str,
            );

            $vars  += $this->buildDate($row->created_at, $Tpl, 'tweet:loop');

            $Tpl->add('tweet:loop', $vars);
            $loop++;

            if ( $loop == 1 ) {
                $args['first_id'] = $row->id_str;
            } elseif ( $loop == $this->limit ) {
                $args['last_id']  = $row->id_str;
            }
        }

        $Tpl->add('pager', $args);

        $fds    = $this->Field->listFields();
        $field  = array();
        foreach ( $fds as $fd ) {
            $field[$fd] = $this->Field->get($fd);
        }

        $Tpl->add(null, $field);
        return true;
    }
}
