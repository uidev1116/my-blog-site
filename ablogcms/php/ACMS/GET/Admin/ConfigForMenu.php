<?php

use Acms\Services\Facades\Config;

class ACMS_GET_Admin_ConfigForMenu extends ACMS_GET_Admin_Config
{
    function & getConfig($rid, $mid, $setid = null)
    {
        $post_config =& $this->Post->getChild('config');
        $config = Config::loadDefaultField();

        if ($setid) {
            $config->overload(Config::loadConfigSet($setid));
        } else {
            $config->overload(Config::loadBlogConfig(BID));
        }
        if (!!$rid && !$mid) {
            $config->overload(Config::loadRuleConfig($rid, $setid));
        }

        $defaultConfig = Config::loadDefaultField();
        $defaultMenuIds = $defaultConfig->getArray('admin_menu_card_id');
        $customMenuIds = $config->getArray('admin_menu_card_id');
        foreach ($defaultMenuIds as $i => $id) {
            if (!in_array($id, $customMenuIds)) {
                $config->add('admin_menu_card_id', $id);
                $config->add('admin_menu_card_title', $defaultConfig->get('admin_menu_card_title', '', $i));
                $config->add('admin_menu_card_url', $defaultConfig->get('admin_menu_card_url', '', $i));
                $config->add('admin_menu_card_icon', $defaultConfig->get('admin_menu_card_icon', '', $i));
                $config->add('admin_menu_card_admin', $defaultConfig->get('admin_menu_card_admin', '', $i));
                $config->add('admin_menu_card_laneid', $defaultConfig->get('admin_menu_card_laneid', '', $i));
            }
        }
        if (!$post_config->isNull()) {
            $config->overload($post_config);
            $post_config->overload($config);
            return $post_config;
        }
        return $config;
    }
}
