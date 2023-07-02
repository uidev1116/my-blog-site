<?php

class ACMS_GET_Touch_Role_RuleEdit extends ACMS_GET
{
    function get()
    {
        return ( roleAuthorization('rule_edit', BID) || !roleAvailableUser() ) ? $this->tpl : false;
    }
}
