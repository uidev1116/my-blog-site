<?php

class ACMS_GET_Admin_User_Tfa extends ACMS_GET
{
    function get()
    {
        if (!Tfa::checkAuthority()) {
            return '';
        }

        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        $tfa = $this->Post->getChild('tfa');
        $vars = array();

        if ($secret = Tfa::getSecretKey(UID)) {
            // 登録済み
            $vars['step'] = 'registered';

        } else {
            // 未登録
            $vars['step'] = 'unregistered';
            $secret = $tfa->get('secret');
            if (empty($secret)) {
                $secret = Tfa::createSecret();
            }
            $vars += array(
                'secret' => $secret,
                'qr-image' => Tfa::getSecretForQRCode($secret, ACMS_RAM::userName(SUID)),
                'secret-txt' => Tfa::getSecretForManual($secret)
            );
        }

        $vars += $this->buildField($this->Post, $Tpl);
        $Tpl->add(null, $vars);
        return $Tpl->get();
    }
}
