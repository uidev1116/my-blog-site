<?php

class ACMS_GET_Touch_CartStock extends ACMS_GET
{
    function get()
    {
        $config = Config::loadDefaultField();
        $config->overload(Config::loadBlogConfig(BID));
        return $config->get('shop_stock_change') === 'on' ? $this->tpl : false;
    }
}
