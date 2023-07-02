<?php

class ACMS_GET_Shop_Customer_Info extends ACMS_GET_Shop
{
    function get()
    {	
        $this->initVars();

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        if ( !!SUID ) {
            $DB  = DB::singleton(dsn());
            $SQL = SQL::newSelect('user');
            $SQL->addSelect('user_code');
            $SQL->addSelect('user_mail');
            $SQL->addSelect('user_name');
            $SQL->addWhereOpr('user_id', SUID);
            $user= $DB->query($SQL->get(dsn()), 'row');
            $Tpl->add('customer', $user);
        } else {
            $Tpl->add('notLogin');
        }

        return $Tpl->get();
    }

}