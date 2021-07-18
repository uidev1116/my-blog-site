<?php

class ACMS_GET_Api_Twitter_Users_Show extends ACMS_GET_Api_Twitter
{
    var $_scope = array(
        'field'     => 'global',
    );

    function get()
    {
        // OAuth認証済みのBID
        $this->id     = $this->bid;
        $this->api    = 'users/show.json';
        $this->params = array_clean(array(
            'user_id'     => ($user_id  = $this->Field->get('user_id'))  ? intval($user_id)  : null,
            'screen_name' => $this->Field->get('screen_name'),
        ));
        $this->crit   = config('twitter_users_show_cache_expire');

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        $this->resolveRequest($Tpl, 'twitter');

        return $Tpl->get();
    }

    function build($response, $Tpl)
    {
        $json   = $this->json_decode($response);

        $vars   = array(
            'friends_count'     => $json->friends_count,
            'statuses_count'    => $json->statuses_count,
            'followers_count'   => $json->followers_count,
            'name'              => $json->name,
            'screen_name'       => $json->screen_name,
            'url'               => $json->url,
            'id'                => $json->id_str,
            'image'             => $json->profile_image_url,
            'l-image'           => $this->largeImageUrl($json->profile_image_url),
            'bg-image'          => $json->profile_background_image_url,
            'p-bg-color'        => $json->profile_background_color,
            'p-txt-color'       => $json->profile_text_color,
            'description'       => $json->description,
            'location'          => $json->location,
            'created_at'        => $json->created_at,
            'duration'          => $this->calcDuration($json->created_at),
        );

        $vars  += $this->buildDate($json->created_at, $Tpl, 'user');

        $Tpl->add('user', $vars);
    }

    function calcDuration($since)
    {
        $since  = strtotime($since);
        $now    = time();

        $dur    = $now - $since;
        return intval($dur / (60*60*24));
    }
}
