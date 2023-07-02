<?php

class ACMS_GET_BackLink extends ACMS_GET
{
    function get()
    {
        if (SUID) {
            $session =& Field::singleton('session');
            $link = $session->get('back_link');
            $eid = intval($session->get('back_link_eid'));
            if ($link && $eid && EID === $eid) {
                $tpl = new Template($this->tpl, new ACMS_Corrector());
                $session->delete('back_link');
                return $tpl->render(array(
                    'url' => $link,
                ));
            }
        }
        return '';
    }
}
