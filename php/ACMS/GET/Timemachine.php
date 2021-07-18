<?php

class ACMS_GET_Timemachine extends ACMS_GET
{
    function get()
    {
        if ( !timemachineMode() ) return false;

        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        $Session =& Field::singleton('session');
        $datetime   = $Session->get('timemachine_datetime');
        list($date, $time)  = preg_split('/\s/', $datetime);

        $Tpl->add(null, array(
            'date'  => $date,
            'time'  => $time,
        ));

        return $Tpl->get();
    }
}
