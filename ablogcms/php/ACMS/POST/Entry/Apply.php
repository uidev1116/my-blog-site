<?php

class ACMS_POST_Entry_Apply extends ACMS_POST_Entry
{
    function post()
    {
        if ( !IS_LICENSED ) die();
        if ( !sessionWithContribution() ) die();
        $Entry  = $this->extract('entry');
        $this->fix($Entry);
        $Field  = $this->extract('field');
        $Column = Entry::extractColumn();

        $action = $this->Post->get('action', (EID ? 'update' : 'insert'));

        return array(
            'step'      => 'reapply',
            'action'    => $action,
            'Entry'     => $Entry,
            'Field'     => $Field,
            'Column'    => $Column,
        );
    }
}
