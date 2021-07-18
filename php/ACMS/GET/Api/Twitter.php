<?php

require_once ACMS_LIB_DIR.'Services/Twitter.php';

class ACMS_GET_Api_Twitter extends ACMS_GET_Api
{
    const WEB_URL = 'https://twitter.com/';

    function largeImageUrl($url)
    {
        return preg_replace('@normal(\.gif|\.jpg|\.png|\.jpeg|\.JPG|\.GIF|\.PNG|\.JPEG|\.bmp|\.BMP)$@', 'bigger$1', $url);
    }

    function miniImageUrl($url)
    {
        return preg_replace('@normal(\.gif|\.jpg|\.png|\.jpeg|\.JPG|\.GIF|\.PNG|\.JPEG|\.bmp|\.BMP)$@', 'mini$1', $url);
    }

    /**
     * twitter認証からユーザー情報を抜き出し
     *
     * @param json
     * @return array
     */
    function extractAccountData($data)
    {
        return array(
            'bid'           => $this->auth_bid,
            'code'          => $data->screen_name,
            'name'          => $data->name,
            'email'         => $data->screen_name.'@example.com',
            'oauth_type'    => 'user_twitter_id',
            'oauth_id'      => $data->id_str,
        );
    }
}