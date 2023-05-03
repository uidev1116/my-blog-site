<?php

class ACMS_GET_Admin_MenuCustom extends ACMS_GET_Admin_Menu
{
    /**
     * @var array
     */
    protected $menus = array();

    /**
     * @var array
     */
    protected $categories = array();

    /**
     * @var array
     */
    protected $defaultMenus = array();

    /**
     * @return run
     */
    public function get()
    {
        $Tpl = new Template($this->tpl, new ACMS_Corrector());

        $this->extractDefaultMenus();
        $this->extractCategories();
        $this->extractMenus();
        $this->build($Tpl);

        return $Tpl->get();
    }

    /**
     * 標準権限下でのメニュー組み立て
     */
    protected function normalAuthMenus()
    {
        // プロフェッショナル版以上
        if (editionWithProfessional()) {
            $this->standardMenu('approval_notification');
            if ($badge = Approval::notificationCount()) {
                $this->defaultMenus['approval_notification']['badge'] = $badge;
            }
        }
        // 投稿者以上
        if (sessionWithContribution()) {
            $this->standardMenu('entry_index');
            $this->standardMenu('entry_trash');
            if (config('media_library') === 'on') {
                $this->standardMenu('media_index', 'media');
            }
        }
        // 編集者以上
        if (sessionWithCompilation()) {
            $this->standardMenu('category_index', 'category');
            $this->standardMenu('tag_index', 'tag');
            $this->standardMenu('comment_index', 'comment');
            $this->standardMenu('trackback_index', 'trackback');
            $this->standardMenu('category_edit', 'category');
            $this->standardMenu('form_index', 'form');
            $this->standardMenu('schedule_index', 'schedule');
            if (config('media_library') === 'on') {
                $this->standardMenu('media_index', 'media');
            }
        }
        // 管理者以上
        if (sessionWithAdministration()) {
            $this->standardMenu('blog_index');
            $this->standardMenu('blog_edit');
            $this->standardMenu('alias_index', 'alias');
            $this->standardMenu('user_index', 'user');
            $this->standardMenu('rule_index', 'rule');
            $this->standardMenu('module_index', 'module');
            $this->standardMenu('webhook_index', 'webhook');
            $this->standardMenu('shortcut_index', 'shortcut');
            $this->standardMenu('publish_index', 'publish');
            $this->standardMenu('backup_index', 'backup');
            $this->standardMenu('import_index', 'import');
            $this->standardMenu('app_index');
            $this->standardMenu('checklist');
            $this->standardMenu('cart_menu');
            $this->standardMenu('fix_index', 'fix');
            if (defined('LICENSE_PLUGIN_SHOP_PRO') and LICENSE_PLUGIN_SHOP_PRO) {
                $this->standardMenu('shop_menu', 'shop');
            }
            if (IS_LICENSED) {
                $this->standardMenu('user_edit', 'user');
                $this->standardMenu('config_set_index', 'config');
                $this->standardMenu('rule_edit', 'rule');
                $this->standardMenu('module_edit', 'module');
                $this->standardMenu('category_edit', 'category');
            }
        }
    }

    /**
     * ロール管理下でのメニュー組み立て
     */
    protected function roleAuthMenus()
    {
        if (editionWithProfessional()) {
            $this->standardMenu('approval_notification');
            if ($badge = Approval::notificationCount()) {
                $this->defaultMenus['approval_notification']['badge'] = $badge;
            }
        }
        if (roleAuthorization('entry_edit', BID, EID)) {
            $this->standardMenu('entry_index');
            $this->standardMenu('entry_trash');
        }
        if (roleAuthorization('category_edit', BID)) {
            $this->standardMenu('category_index', 'category');
        }
        if (roleAuthorization('tag_edit', BID)) {
            $this->standardMenu('tag_index', 'tag');
        }
        if (roleAuthorization('media_upload', BID) || roleAuthorization('media_edit', BID)) {
            if (config('media_library') === 'on') {
                $this->standardMenu('media_index', 'media');
            }
        }
        if (roleAuthorization('rule_edit', BID)) {
            $this->standardMenu('rule_index', 'rule');
            $this->standardMenu('rule_edit', 'rule');
        }
        if (roleAuthorization('publish_edit', BID) || roleAuthorization('publish_exec', BID)) {
            $this->standardMenu('publish_index', 'publish');
        }
        if (roleAuthorization('config_edit', BID) && IS_LICENSED) {
            $this->standardMenu('config_set_index', 'config');
        }
        if (roleAuthorization('module_edit', BID)) {
            $this->standardMenu('module_index', 'module');
            $this->standardMenu('module_edit', 'module');
        }
        if (roleAuthorization('backup_export', BID) || roleAuthorization('backup_import', BID)) {
            $this->standardMenu('backup_index', 'backup');
        }
        if (roleAuthorization('form_view', BID) || roleAuthorization('form_edit', BID)) {
            $this->standardMenu('form_index', 'form');
        }
        if (roleAuthorization('admin_etc', BID)) {
            $this->standardMenu('comment_index', 'comment');
            $this->standardMenu('trackback_index', 'trackback');
            $this->standardMenu('blog_index');
            $this->standardMenu('blog_edit');
            $this->standardMenu('webhook_index', 'webhook');
            $this->standardMenu('alias_index', 'alias');
            $this->standardMenu('user_index', 'user');
            $this->standardMenu('shortcut_index', 'shortcut');
            $this->standardMenu('schedule_index', 'schedule');
            $this->standardMenu('import_index', 'import');
            $this->standardMenu('app_index', 'app');
            $this->standardMenu('checklist');
            $this->standardMenu('cart_menu');
            if (defined('LICENSE_PLUGIN_SHOP_PRO') and LICENSE_PLUGIN_SHOP_PRO) {
                $this->standardMenu('shop_menu', 'shop');
            }
            if (IS_LICENSED) {
                $this->standardMenu('user_edit', 'user');
            }
        }
    }

    /**
     * デフォルトメニューの変数を組み立て
     */
    protected function extractDefaultMenus()
    {
        if (roleAvailableUser()) {
            $this->roleAuthMenus();
        } else {
            $this->normalAuthMenus();
        }
        if (BID === RBID && sessionWithEnterpriseAdministration()) {
            $this->standardMenu('role_index', 'role');
            $this->standardMenu('usergroup_index', 'usergroup');
            $this->standardMenu('approval_index', 'approval_index');
        }
        if (1
            && BID == RBID
            && !sessionWithEnterpriseAdministration()
            && sessionWithProfessionalAdministration()
        ) {
            $this->standardMenu('approval_index', 'approval_index');
        }
        if (sessionWithProfessionalAdministration() || sessionWithEnterpriseAdministration()) {
            $this->standardMenu('static-export_index', 'static-export_index');
        }
    }

    /**
     * メニューのURLを組み立て
     *
     * @param $admin
     * @param bool $linkCheck
     */
    protected function standardMenu($admin, $linkCheck = false)
    {
        if (empty($linkCheck)) {
            $linkCheck = $admin;
        }
        $this->defaultMenus[$admin] = array(
            'url' => acmsLink(array('admin' => $admin, 'bid' => BID)),
            'stay' => $this->linkCheck($linkCheck),
        );
    }

    /**
     * テンプレートを組み立て
     *
     * @param $Tpl
     */
    protected function build(& $Tpl)
    {
        foreach ($this->categories as $id => $category) {
            $hasMenu = false;
            if (!isset($this->menus[$id])) {
                continue;
            }
            foreach ($this->menus[$id] as $menu) {
                if ($menu['admin']) {
                    if ($default = $this->defaultMenu($menu)) {
                        $menu = $default;
                    } else {
                        continue;
                    }
                }
                $hasMenu = true;
                $Tpl->add(array('menus:loop', 'categories:loop'), $menu);
            }
            if ($hasMenu) {
                $Tpl->add('categories:loop', array(
                    'title' => $category,
                ));
            }
        }
    }

    /**
     * デフォルトメニューの取得
     *
     * @param array $menu
     * @return bool | array
     */
    protected function defaultMenu($menu)
    {
        $id = $menu['id'];
        if (!isset($this->defaultMenus[$id])) {
            return false;
        }
        $data = $this->defaultMenus[$id];
        $menu['url'] = $data['url'];
        $menu['stay'] = $data['stay'];
        if (isset($data['badge'])) {
            $menu['badge'] = $data['badge'];
        }
        return $menu;
    }

    /**
     * コンフィグからメニューカテゴリーの抜き出し
     */
    protected function extractCategories()
    {
        $ids = configArray('admin_menu_lane_id');
        foreach ($ids as $i => $id) {
            if ($i === 0) {
                continue;
            }
            $this->categories[$id] = config('admin_menu_lane_title', '', $i);
        }
    }

    /**
     * コンフィグからメニューを抜き出し
     */
    protected function extractMenus()
    {
        $ids = configArray('admin_menu_card_laneid');

        foreach ($ids as $i => $id) {
            if (!isset($this->menus[$id])) {
                $this->menus[$id] = array();
            }
            $this->menus[$id][] = array(
                'id' => config('admin_menu_card_id', '', $i),
                'title' => config('admin_menu_card_title', '', $i),
                'url' => setGlobalVars(config('admin_menu_card_url', '', $i)),
                'admin' => config('admin_menu_card_admin', '', $i) === 'true',
                'icon' => config('admin_menu_card_icon', '', $i),
            );
        }
    }
}
