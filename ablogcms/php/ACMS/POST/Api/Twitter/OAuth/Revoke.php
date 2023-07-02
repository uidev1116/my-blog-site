<?php

class ACMS_POST_Api_Twitter_OAuth_Revoke extends ACMS_POST_Api_Twitter
{
    function post()
    {
        ACMS_Services_Twitter::deleteOAuthToken(BID);

        return $this->Post;
    }
}
