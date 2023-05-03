<?php

class ACMS_POST_License_Activation extends ACMS_POST
{
    /**
     * @return bool|Field
     */
    function post()
    {
        if (!sessionWithAdministration()) {
            die('Permission denied.');
        }
        try {
            $licenseFilePath = CACHE_DIR . 'license.php';
            Storage::remove($licenseFilePath);

            $json = \App::licenseActivation($licenseFilePath);
            if (empty($json)) {
                throw new \RuntimeException(i18n('不明なエラーが発生しました'));
            }
            if ($json && $json->status === 'failed') {
                throw new \RuntimeException($json->message);
            }
            $this->addMessage(i18n('サブスクリプションライセンスの有効化に成功しました。'));
        } catch (\Exception $e) {
            $this->addError($e->getMessage());
        }

        $this->redirect(acmsLink(array(
            'bid' => BID,
            'admin' => 'checklist',
        )));
    }
}
