<?php

class ACMS_GET_Admin_Messages extends ACMS_GET_Admin
{
    function get()
    {
        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        $session =& Field::singleton('session');

        $messages = false;
        if ($this->Post->isChildExists('messages')) {
            $messages = $this->Post->getChild('messages');
        } else if ($session->isChildExists('messages')) {
            $messages = $session->getChild('messages');
            $session->removeChild('messages');
        }
        if (empty($messages)) {
            return '';
        }
        $vars = $this->buildField($messages, $Tpl);
        $Tpl->add(null, $vars);

        return $Tpl->get();
    }
}
