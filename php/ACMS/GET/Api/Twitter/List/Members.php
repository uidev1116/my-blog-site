<?php

class ACMS_GET_Api_Twitter_List_Members extends ACMS_GET_Api_Twitter
{
    var $_scope = array(
        'field'     => 'global',
    );

    function get()
    {
        $user   = $this->Field->get('user');
        $list   = $this->Field->get('list');

        // OAuth認証済みのBID
        $this->id     = $this->bid;
        $this->api    = "lists/members.json";
        $this->params = array_clean(array(
            'slug'              => $list,
            'owner_screen_name' => $user,
            'cursor'            => $this->Field->get('cursor'),
        ));
        $this->crit = config('twitter_list_members_cache_expire');

        $Tpl = new Template($this->tpl, new ACMS_Corrector());

        $this->resolveRequest($Tpl, 'twitter');

        return $Tpl->get();
    }

    function build($response, $Tpl)
    {
        $json = json_decode($response);

        if ( $json === false ) {
            $Tpl->add('unavailable');
            return false;
        }

        if ( count($json) === 0 ) {
            $Tpl->add('notFound');
            return false;
        }

        foreach ( $json->users as $user ) {

            $vars   = array(
                'friends_count'     => $user->friends_count,
                'statuses_count'    => $user->statuses_count,
                'followers_count'   => $user->followers_count,
                'name'              => $user->name,
                'screen_name'       => $user->screen_name,
                'url'               => $user->url,
                'id'                => $user->id,
                'image'             => $user->profile_image_url,
                'l-image'           => $this->largeImageUrl($user->profile_image_url),
                'bg-image'          => $user->profile_background_image_url,
                'p-bg-color'        => $user->profile_background_color,
                'p-txt-color'       => $user->profile_text_color,
                'description'       => $user->description,
                'location'          => $user->location,
                'created_at'        => $user->created_at,
                'duration'          => $this->calcDuration($user->created_at),
            );
            $Tpl->add('member:loop', $vars);
        }

        $vars   = array();
        if ( $json->next_cursor != 0 )       $vars['next']   = $json->next_cursor;
        if ( $json->previous_cursor != 0 )   $vars['prev']   = $json->previous_cursor;

        $Tpl->add('pager', $vars);
        $Tpl->add(null);
    }

    function calcDuration($since)
    {
        $since  = strtotime($since);
        $now    = time();

        $dur    = $now - $since;
        return intval($dur / (60*60*24));
    }
}
