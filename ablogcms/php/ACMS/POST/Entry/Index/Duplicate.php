<?php

class ACMS_POST_Entry_Index_Duplicate extends ACMS_POST_Entry_Duplicate
{
    function post()
    {
        $this->Post->reset(true);
        $this->Post->setMethod('entry', 'operative', sessionWithContribution());
        $this->Post->setMethod('checks', 'required');
        $this->Post->validate(new ACMS_Validator());

        if ( $this->Post->isValidAll() ) {
            foreach ( $this->Post->getArray('checks') as $eid ) {
                $id = preg_split('@:@', $eid, 2, PREG_SPLIT_NO_EMPTY);
                $eid = $id[1];
                if (!$this->validate($eid)) {
                    continue;
                }
                $this->duplicate($eid);
            }
        }

        return $this->Post;
    }
}
