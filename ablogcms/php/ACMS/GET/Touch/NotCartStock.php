<?php

class ACMS_GET_Touch_NotCartStock extends ACMS_GET
{
    function get()
    {
        $config = Config::loadDefaultField();
        $config->overload(Config::loadBlogConfig(BID));
        return $config->get('shop_stock_change') == 'on' ? false : $this->tpl;
    }
}
