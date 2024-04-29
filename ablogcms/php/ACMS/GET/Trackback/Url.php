<?php

class ACMS_GET_Trackback_Url extends ACMS_GET
{
    public $_scope    = [
        'eid'   => 'global',
    ];

    function get()
    {
        if (!$this->eid) {
            return '';
        }
        if ('on' <> config('trackback')) {
            return '';
        }

        $Tpl    = new Template($this->tpl);
        $Tpl->add(null, [
            'url'   => acmsLink([
                'bid'   => BID,
                'eid'   => $this->eid,
                'trackback' => true,
            ]),
        ]);

        return $Tpl->get();
    }
}
