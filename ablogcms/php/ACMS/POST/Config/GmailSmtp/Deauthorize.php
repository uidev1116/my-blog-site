<?php

use Acms\Services\Facades\Cache;
use Acms\Services\Facades\Application;
use Acms\Services\Facades\Logger as AcmsLogger;
use Acms\Services\Mailer\Transport\GoogleApi;

class ACMS_POST_Config_GmailSmtp_Deauthorize extends ACMS_POST
{
    public function post()
    {
        if (!sessionWithAdministration()) {
            $this->addError('不正な操作です。');
            return $this->Post;
        }
        $api = Application::make('mailer.google.smtp.api');
        assert($api instanceof GoogleApi);
        $setid = intval($this->Get->get('setid'));
        if (empty($setid)) {
            $setid = null;
        }
        $api->init(BID, $setid);
        $client = $api->getClient();
        $client->revokeToken();
        $this->deleteAccessToken($api->getAccessTokenConfigKey(), BID, $setid);
        AcmsLogger::info('Gmail API の OAuth認証を解除しました。');

        $urlContext = [
            'bid' => BID,
            'admin' => 'config_mail',
        ];
        if ($setid) {
            $urlContext['query'] = [
                'setid' => $setid,
            ];
        }
        $this->redirect(acmsLink($urlContext));
    }

    /**
     * アクセストークン情報をDBから削除
     *
     * @param string $accessTokenConfigKey
     * @param int $bid
     * @param null|int $setid
     * @return void
     */
    protected function deleteAccessToken(string $accessTokenConfigKey, int $bid, ?int $setid = null): void
    {
        $sql = SQL::newDelete('config');
        $sql->addWhereOpr('config_key', $accessTokenConfigKey);
        $sql->addWhereOpr('config_blog_id', $bid);
        $sql->addWhereOpr('config_set_id', $setid);
        DB::query($sql->get(dsn()), 'exec');
        Cache::flush('config');
    }
}
