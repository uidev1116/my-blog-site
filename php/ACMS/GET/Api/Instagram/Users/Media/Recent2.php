<?php

class ACMS_GET_Api_Instagram_Users_Media_Recent2 extends ACMS_GET_Api
{
    var $_scope = array(
        'field'     => 'global',
    );

    function get() {

        $instagram = App::make('instagram-login');
        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        $this->buildModuleField($Tpl);

        try {
            $limit = config('instagram_users_media_recent_limit');
            $businessAccount = config('instagram_users_media_recent_account');
            $maxId = ($next_max_id = $this->Field->get('next_max_id')) ? intval($next_max_id) : null;
            $this->params = array(
                'nextPageId' => $maxId,
                'limit' => $limit,
                'businessAccount' => $businessAccount
            );
            $this->id = $this->bid;
            $this->crit  = config('instagram_users_media_recent_cache_expire');
            $this->api  = 'instagram graph api media';
            $hash  = $this->getHash();
            $cache = $this->detectCache($hash);
            if (!$cache) {
                $accessToken = $instagram->loadAccessToken(BID);
                $instagram->setAccessToken($accessToken);
                $instagram->setMe();
                $instagramMedia = $instagram->getMedia($this->params);
                $this->saveCache($hash, $this->crit, json_encode($instagramMedia));
            } else {
                $instagramMedia = json_decode($cache, true);
            }
            $media = $instagramMedia['media'];
            $pager = $instagramMedia['pager'];
            $userIcon = $instagramMedia['userIcon'];
            
            foreach ( $media as $photo ) {
              $vars = array(
                  'img'           => $photo['media_url'],
                  'link'          => $photo['permalink'],
                  'caption'       => isset($photo['caption']) ? $photo['caption'] : '',
                  'userName'      => $photo['username'],
                  'userId'        => $photo['owner']['id'],
                  'createdTime'   => date('Y-m-d H:i:s', strtotime($photo['timestamp'])),
                  'type'          => $photo['media_type'],
                  'profileImg'    => $userIcon,
                  'countComments' => $photo['comments_count'],
                  'countLikes'    => $photo['like_count'],
              );
              $Tpl->add('photo:loop', $vars);
            }
            if ($pager && isset($pager['cursors']) && isset($pager['next'])) {
                $Tpl->add('pager', array_clean(array(
                    'next_max_id'   => $pager['cursors']['after']
                )));
            }
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            $Tpl->add('error', array(
                'message' => $e->getMessage()
            ));
        } catch  (\RuntimeException $e) {
            $Tpl->add('error', array(
                'message' => $e->getMessage()
            ));
        }
        return $Tpl->get();
    }
}