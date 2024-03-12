<?php

class ACMS_POST_Entry_Confirm extends ACMS_POST_Entry
{
    function post()
    {
        if (!IS_LICENSED) {
            die();
        }
        if (!sessionWithContribution()) {
            die();
        }
        $Entry  = $this->extract('entry', new ACMS_Validator());
        $this->fix($Entry);
        $Field  = $this->extract('field', new ACMS_Validator());

        $Column = Entry::extractColumn();

        $step   = $this->Post->get('step', 'reapply');
        $action = $this->Post->get('action', (EID ? 'update' : 'insert'));

        if ($Entry->isValid() and $Field->isValid()) {
            $step = 'confirm';
        }
        return array(
            'step'      => $step,
            'action'    => $action,
            'Entry'     => $Entry,
            'Field'     => $Field,
            'Column'    => $Column,
        );
    }
}
