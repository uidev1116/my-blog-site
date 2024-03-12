<?php

class ACMS_POST_Shop2_Form_Backstep extends ACMS_POST_Shop2
{
    function post()
    {
        $this->initVars();

        switch ($this->Post->get('step')) {
            case 'address':
                $this->Get->set('step', '');
                break;
            case 'deliver':
                $this->Get->set('step', 'address');
                break;
            case 'confirm':
                $this->Get->set('step', 'deliver');
                break;
            default:
                break;
        }

        $this->screenTrans($this->orderTpl, $this->Get->get('step'));
    }
}
