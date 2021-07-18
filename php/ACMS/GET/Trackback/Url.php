<?php

class ACMS_GET_Trackback_Url extends ACMS_GET
{
    var $_scope    = array(
        'eid'   => 'global',
    );

    function get()
    {
        if ( !$this->eid ) return '';
        if ( 'on' <> config('trackback') ) return '';

        $Tpl    = new Template($this->tpl);
        $Tpl->add(null, array(
            'url'   => acmsLink(array(
                'bid'   => BID,
                'eid'   => $this->eid,
                'trackback' => true,
            )),
        ));

        return $Tpl->get();

    }
}
