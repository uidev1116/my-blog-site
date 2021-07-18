<?php

class ACMS_GET_Touch_Role_NotRuleEdit extends ACMS_GET
{
    function get()
    {
        return roleAuthorization('rule_edit', BID) ? false : $this->tpl;
    }
}
