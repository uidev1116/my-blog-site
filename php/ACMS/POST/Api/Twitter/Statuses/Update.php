<?php

class ACMS_POST_Api_Twitter_Statuses_Update extends ACMS_POST_Api_Twitter
{
    function post()
    {
        if ( !SUID ) return false;

        $bid    = BID;
        $Field  = $this->extract('twitter');
        $tweet  = $Field->get('tweet');

        return $this->tweet($tweet, $bid);
    }

    function tweet($tweet, $bid, $chain = false)
    {
        $API    = ACMS_Services_Twitter::establish($bid);
        $params = array(
            'status'    => $tweet,
        );

        if ( !!($API->httpRequest('statuses/update.json', $params, 'POST')) ) {
            if ( $chain ) {
                return true;
            } else {
                $this->redirect(acmsLink());
            }
        } else {
            // read onlyだとダメなので，何らか通知したい
            return false;
        }
    }
}
