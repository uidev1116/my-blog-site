<?php

class ACMS_POST_Api_Twitter_OAuth_DirectSave extends ACMS_POST_Api_Twitter
{
    function post()
    {
        $Twitter = $this->extract('twitter');

        ACMS_Services_Twitter::insertAcsToken(BID, $Twitter->get('access_token'), $Twitter->get('access_token_secret'));

        return $this->Post;
    }
}
