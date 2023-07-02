<?php

class ACMS_GET_Admin_Config_Theme extends ACMS_GET
{
    function get()
    {
        $Tpl = new Template($this->tpl, new ACMS_Corrector());

        $themesDir = opendir(SCRIPT_DIR.THEMES_DIR);
        $index = 0;

        //-------------------
        // [CMS-1760]
        // Cookieによりテーマが設定されているとそのテーマが選択されてしまい
        // 本来のテーマ設定が分からない為、DBから直接取得
        if ( !($rid = intval($this->Get->get('rid'))) ) { $rid = null; }
        if ( !($setid = intval($this->Get->get('setid'))) ) { $setid = null; }

        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('config');
        $SQL->addSelect('config_value');
        $SQL->addWhereOpr('config_key', 'theme');
        $SQL->addWhereOpr('config_rule_id', $rid);
        $SQL->addWhereOpr('config_set_id', $setid);
        $SQL->addWhereOpr('config_blog_id', BID);
        $q      = $SQL->get(dsn());
        $config_theme = $DB->query($q, 'one');

        if ( !$config_theme ) {
            $SQL    = SQL::newSelect('config');
            $SQL->addSelect('config_value');
            $SQL->addWhereOpr('config_key', 'theme');
            $SQL->addWhereOpr('config_rule_id', null);
            $SQL->addWhereOpr('config_set_id', $setid);
            $SQL->addWhereOpr('config_blog_id', BID);
            $q      = $SQL->get(dsn());
            $config_theme = $DB->query($q, 'one');
        }

        if ( !$config_theme ) {
            $configDefaultArray = loadDefaultConfig();
            $config_theme       = $configDefaultArray['theme'];
        }

        $themesDirList = array();
        while ( $theme = readdir($themesDir) ) {
            $themesDirList[] = $theme;
        }

        @sort($themesDirList);

        foreach ( $themesDirList as $theme ) {
            if ( 1
                and Storage::isDirectory(SCRIPT_DIR.THEMES_DIR.$theme)
                and $theme !== 'system'
                and $theme !== '.'
                and $theme !== '..'
            ) {
                $selected = $config_theme === $theme ? ' selected="selected"' : '';
                $Tpl->add('theme:loop', array(
                    'name'      => $theme,
                    'selected'  => $selected,
                    'key'       => $index,
                ));

                $selected = $config_theme === $theme ? '' : 'js-load_hide_box';
                $TplSetting = array();
                while ( !empty($theme) ) {
                    if ( $_TplSetting = Config::yamlLoad(SCRIPT_DIR.THEMES_DIR.$theme.'/template.yaml') ) {
                        foreach ( $_TplSetting as $key => $val ) {
                            if ( !(isset($TplSetting[$key]) && !empty($TplSetting[$key])) ) {
                                $TplSetting[$key] = $val;
                            }
                        }
                    }
                    $theme  = preg_replace('/^[^@]*?(@|$)/', '', $theme);
                }

                if ( !empty($TplSetting) ) {
                    $TplSetting['js-load_hide_box'] = $selected;
                    $TplSetting['theme'] = $theme;
                    $TplSetting['key'] = $index;
                    $Tpl->add('template:loop', $TplSetting);
                } else {
                    $Tpl->add('template:loop', array(
                        'not_found'         => $theme,
                        'js-load_hide_box'  => $selected,
                        'key'               => $index,
                    ));
                }
                $index++;
            }
        }
        closedir($themesDir);

        if ( sessionWithAdministration() ) {
            if ( !empty($mid) ) {
                $url    = acmsLink(array(
                    'bid'   => BID,
                    'admin' => 'module_index',
                ));
            } else if ( !empty($rid) ) {
                $url    = acmsLink(array(
                    'bid'   => BID,
                    'admin' => 'config_index',
                    'query' => array(
                        'rid'   => $rid,
                    ),
                ));
            } else if ( 'shop' == substr(ADMIN, 0, 4) ) {
                $url    = acmsLink(array(
                    'bid'   => BID,
                    'admin' => 'shop_menu',
                ));
            } else {
                $url    = acmsLink(array(
                    'bid'   => BID,
                    'admin' => 'config_index',
                ));
            }
        } else {
            $url    = acmsLink(array(
                'bid'   => BID,
                'admin' => 'top',
            ));
        }
        $Tpl->add(null, array(
            'indexUrl'  => $url,
            'theme'     => $config_theme,
        ));

        return $Tpl->get();
    }
}
