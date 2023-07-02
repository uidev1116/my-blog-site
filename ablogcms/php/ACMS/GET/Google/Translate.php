<?php

/**
 * Class AAPP_GoogleTranslate_GET_Google_Translate
 *
 */
class ACMS_GET_Google_Translate extends ACMS_GET
{
    public function get()
    {
        $client = new Google_Client();
        $client->setDeveloperKey(config('google_translate_api_key'));
        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        $targetLanguage = 'en';
        if (!($this->Get->get('text'))) {
            return $Tpl->get();
        } else {
            $txt = $this->Get->get('text');
        }
        if ($this->Get->get('target_language')) {
            $targetLanguage = $this->Get->get('target_language');
        }
        $service = new Google_Service_Translate($client);
        $translations = $service->translations->listTranslations($txt, $targetLanguage);
        return $Tpl->render($translations['modelData']);
    }
}
