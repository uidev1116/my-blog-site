<?php

class ACMS_GET_Admin_Config_Menu extends ACMS_GET_Admin
{
  function & getConfig($rid, $mid)
  {
      $config = Config::loadDefaultField();
      $config->overload(Config::loadBlogConfig(BID));
      $_config = null;

      if ( !!$rid && !$mid ) {
          $_config = Config::loadRuleConfig($rid);
      } else if ( !!$mid ) {
          $_config = Config::loadModuleConfig($mid, $rid);
      }

      if ( !!$_config ) {
          $config->overload($_config);
          foreach ( array(
              'admin_menu_lane_title', 
              'admin_menu_lane_id', 
              'admin_menu_card_title',
              'admin_menu_card_url',
              'admin_menu_card_laneid',
              'admin_menu_card_id'
            ) as $fd
          ) {
              $config->setField($fd, $_config->getArray($fd));
          }

      }
      return $config;
  }
  function get()
  {
      if ( !IS_LICENSED ) return ''; 
      if ( !$rid = idval(ite($_GET, 'rid')) ) $rid = null;
      if ( !$mid = idval(ite($_GET, 'mid')) ) $mid = null;

      $Config     =& $this->getConfig($rid, $mid);
      $laneIds = $Config->getArray('admin_menu_lane_id');
      $cardIds = $Config->getArray('admin_menu_card_id');
      $lanes = array();

      foreach ($laneIds as $i => $laneId) {
        $lanes[] = array(
          'id' => $laneId,
          'title' => $Config->get('admin_menu_lane_title', '', $i)
        );
      }

      foreach ($cardIds as $i => $cardId) {
        $cards = array(
          'id' => $cardId,
          'title' => $Config->get('admin_menu_card_title', '', $i)
        );
      }
  }
}