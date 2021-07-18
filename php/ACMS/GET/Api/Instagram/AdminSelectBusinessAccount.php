<?php

class ACMS_GET_Api_Instagram_AdminSelectBusinessAccount extends ACMS_GET
{
  function get()
  {
      $Tpl = new Template($this->tpl, new ACMS_Corrector());

      if (!isFacebookLoginAvailable()) {
          $Tpl->add('notFoundKeys');
          return $Tpl->get();
      }

      $instagram = App::make('instagram-login');

      try {
          if ($accessToken = $instagram->loadAccessToken(BID)) {
              $instagram->setAccessToken($accessToken);
              $instagram->setMe();
              $accounts = $instagram->getBussinessAccounts();
              return $Tpl->render(array(
                  'accounts' => $accounts,
                  'accountsCount' => count($accounts)
              ));
          }
      } catch (\Exception $e) {
          return $Tpl->render(array(
              'error' => $e->getMessage()
          ));
      }
      return $Tpl->render(array(
        'error' => 'oAuth認証が必要です。'
      ));
  }
}
