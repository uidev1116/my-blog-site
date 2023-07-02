<?php

require_once ACMS_LIB_DIR.'Services/Twitter.php';

class ACMS_POST_Api_Twitter extends ACMS_POST_Api
{
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
