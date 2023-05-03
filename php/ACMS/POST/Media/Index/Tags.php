<?php

class ACMS_POST_Media_Index_Tags extends ACMS_POST_Media_Tags
{
    function post()
    {
        $this->Post->reset(true);
        $this->Post->setMethod('checks', 'required');
        $this->Post->setMethod('tags', 'required');
        $this->Post->validate(new ACMS_Validator());

        if ( $this->Post->isValidAll() ) {
            @set_time_limit(0);
            foreach ( $this->Post->getArray('checks') as $mid ) {
                $id     = preg_split('@:@', $mid, 2, PREG_SPLIT_NO_EMPTY);
                $mbid   = intval($id[0]);
                $mid    = intval($id[1]);
                if (!(1
                    && $mid && $mbid
                    && ACMS_RAM::blogLeft(SBID) <= ACMS_RAM::blogLeft($mbid)
                    && ACMS_RAM::blogRight(SBID) >= ACMS_RAM::blogRight($mbid)
                )) {
                    continue;
                }
                $this->addTag($mid, $mbid, $this->Post->get('tags'));
            }
        }
        die();
    }
}
