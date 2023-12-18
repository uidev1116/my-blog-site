<?php

class ACMS_POST_Approval extends ACMS_POST_Entry
{

}

class ACMS_Validator_Approval extends ACMS_Validator
{
    function date($date)
    {
        return preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $date);
    }

    function time($time)
    {
        return preg_match('/^[0-9]{2}:[0-9]{2}:[0-9]{2}$/', $time);
    }
}