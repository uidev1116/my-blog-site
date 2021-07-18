<?php

class ACMS_GET_Touch_NotSearchEngineKeyword extends ACMS_GET
{
    function get()
    {
        return !SEARCH_ENGINE_KEYWORD ? $this->tpl : '';
    }
}
