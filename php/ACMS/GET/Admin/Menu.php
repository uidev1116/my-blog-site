<?php

use Acms\Services\Update\System\CheckForUpdate;

class ACMS_GET_Admin_Menu extends ACMS_GET_Admin
{
    function linkCheck($type)
    {
        if (1
            && strpos($type, '_') === false
            && $type !== 'checklist'
            && $type !== 'update'
        ) {
            $type = $type.'_';
        }
        $reg    = '/^'.$type.'/';
        $stay   = ' class="stay"';
        if ( $type == 'top_' && ADMIN == 'top' ) {
            return $stay;
        }
        if ( preg_match($reg, ADMIN) ) {
            return $stay;
        } else {
            return '';
        }
    }

    function roleAuth(& $Tpl)
    {
        $Tpl->add('dashboard', array(
            'url'   => acmsLink(array('admin' => 'top', 'bid' => BID)),
            'stay'  => $this->linkCheck('top'),
        ));

        if (editionWithProfessional()) {
            $approval = array(
                'url'   => acmsLink(array('admin' => 'approval_notification', 'bid' => BID)),
                'stay'  => $this->linkCheck('approval_notification'),
            );
            if ( $badge = Approval::notificationCount() ) {
                $approval['badge'] = $badge;
            }
            $Tpl->add('approval#notification', $approval);
        }

        if ( roleAuthorization('entry_edit', BID, EID) ) {
            $Tpl->add('entry#index', array(
                'url'   => acmsLink(array('admin' => 'entry_index', 'bid' => BID)),
                'stay'  => $this->linkCheck('entry_index'),
            ));
            $Tpl->add('entry#trash', array(
                'url'   => acmsLink(array('admin' => 'entry_trash', 'bid' => BID)),
                'stay'  => $this->linkCheck('entry_trash'),
            ));
            if ( IS_LICENSED ) $Tpl->add('entry#insert');
        }

        if ( roleAuthorization('category_edit', BID) ) {
            $Tpl->add('category#index', array(
                'url'   => acmsLink(array('admin' => 'category_index', 'bid' => BID)),
                'stay'  => $this->linkCheck('category'),
            ));
            if ( IS_LICENSED ) {
                $Tpl->add('category#insert', array(
                    'url'   => acmsLink(array('admin' => 'category_edit', 'bid' => BID)),
                    'stay'  => $this->linkCheck('category'),
                ));
            }
        }

        if ( roleAuthorization('tag_edit', BID) ) {
            $Tpl->add('tag', array(
                'url'   => acmsLink(array('admin' => 'tag_index', 'bid' => BID)),
                'stay'  => $this->linkCheck('tag'),
            ));
        }

        if ( roleAuthorization('media_upload', BID) || roleAuthorization('media_edit', BID) ) {
            if ( config('media_library') === 'on' ) {
                $Tpl->add('media#index', array(
                    'url'   => acmsLink(array('bid' => BID, 'admin' => 'media_index')),
                    'stay'  => $this->linkCheck('media'),
                ));
            }
        }

        if ( roleAuthorization('rule_edit', BID) ) {
            $Tpl->add('rule#index', array(
                'url'   => acmsLink(array('admin' => 'rule_index', 'bid' => BID)),
                'stay'  => $this->linkCheck('rule'),
            ));
            if ( IS_LICENSED ) {
                $Tpl->add('rule#insert', array(
                    'url'   => acmsLink(array('admin' => 'rule_edit', 'bid' => BID)),
                    'stay'  => $this->linkCheck('rule'),
                ));
            }
        }

        if ( roleAuthorization('publish_edit', BID) || roleAuthorization('publish_exec', BID) ) {
            $Tpl->add('publish#index', array(
                'url'   => acmsLink(array('bid' => BID, 'admin' => 'publish_index')),
                'stay'  => $this->linkCheck('publish'),
            ));

        }

        if ( roleAuthorization('config_edit', BID) ) {
            if ( IS_LICENSED ) {
                $Tpl->add('config#set_index', array(
                    'url'   => acmsLink(array('admin' => 'config_set_index', 'bid' => BID)),
                    'stay'  => $this->linkCheck('config'),
                ));
            }
        }

        if ( roleAuthorization('module_edit', BID) ) {
            $Tpl->add('module#index', array(
                'url'   => acmsLink(array('admin' => 'module_index', 'bid' => BID)),
                'stay'  => $this->linkCheck('module'),
            ));
            if ( IS_LICENSED ) {
                $Tpl->add('module#insert', array(
                    'url'   => acmsLink(array('admin' => 'module_edit', 'bid' => BID)),
                    'stay'  => $this->linkCheck('module'),
                ));
            }
        }

        if ( roleAuthorization('backup_export', BID) || roleAuthorization('backup_import', BID) ) {
            $Tpl->add('backup#index', array(
                'url'   => acmsLink(array('bid' => BID, 'admin' => 'backup_index')),
                'stay'  => $this->linkCheck('backup'),
            ));
        }

        if ( roleAuthorization('form_view', BID) || roleAuthorization('form_edit', BID) ) {
            $Tpl->add('form#index', array(
                'url'   => acmsLink(array('bid' => BID, 'admin' => 'form_index')),
                'stay'  => $this->linkCheck('form'),
            ));
        }

        if ( roleAuthorization('admin_etc', BID) ) {
            $Tpl->add('comment', array(
                'url'   => acmsLink(array('bid' => BID, 'admin' => 'comment_index')),
                'stay'  => $this->linkCheck('comment'),
            ));
            $Tpl->add('trackback', array(
                'url'   => acmsLink(array('bid' => BID, 'admin' => 'trackback_index')),
                'stay'  => $this->linkCheck('trackback'),
            ));
            $Tpl->add('blog#index', array(
                'url'   => acmsLink(array('admin' => 'blog_index', 'bid' => BID)),
                'stay'  => $this->linkCheck('blog_index'),
            ));
            $Tpl->add('blog#edit', array(
                'url'   => acmsLink(array('admin' => 'blog_edit', 'bid' => BID)),
                'stay'  => $this->linkCheck('blog_edit'),
            ));
            $Tpl->add('alias#index', array(
                'url'   => acmsLink(array('admin' => 'alias_index', 'bid' => BID)),
                'stay'  => $this->linkCheck('alias'),
            ));
            $Tpl->add('user#index', array(
                'url'   => acmsLink(array('admin' => 'user_index', 'bid' => BID)),
                'stay'  => $this->linkCheck('user'),
            ));
            $Tpl->add('shortcut#index', array(
                'url'   => acmsLink(array('admin' => 'shortcut_index', 'bid' => BID)),
                'stay'  => $this->linkCheck('shortcut'),
            ));
            $Tpl->add('schedule#index', array(
                'url'   => acmsLink(array('bid' => BID, 'admin' => 'schedule_index')),
                'stay'  => $this->linkCheck('schedule'),
            ));
            $Tpl->add('import#index', array(
                'url'   => acmsLink(array('bid' => BID, 'admin' => 'import_index')),
                'stay'  => $this->linkCheck('import'),
            ));
            $Tpl->add('app#index', array(
                'url'   => acmsLink(array('bid' => BID, 'admin' => 'app_index')),
                'stay'  => $this->linkCheck('app_index'),
            ));
            $Tpl->add('checklist', array(
                'url'   => acmsLink(array('bid' => BID, 'admin' => 'checklist')),
                'stay'  => $this->linkCheck('checklist'),
            ));
            $Tpl->add('cart#menu', array(
                'url'   => acmsLink(array('bid' => BID, 'admin' => 'cart_menu')),
                'stay'  => $this->linkCheck('cart_menu'),
            ));
            if ( defined('LICENSE_PLUGIN_SHOP_PRO') and LICENSE_PLUGIN_SHOP_PRO ) {
                $Tpl->add('shop#menu', array(
                    'url'   => acmsLink(array('bid' => BID, 'admin' => 'shop_menu')),
                    'stay'  => $this->linkCheck('shop'),
                ));
            }
            if ( IS_LICENSED ) {
                $Tpl->add('user#insert', array(
                    'url'   => acmsLink(array('admin' => 'user_edit', 'bid' => BID)),
                    'stay'  => $this->linkCheck('user#insert'),
                ));
                if ( isBlogGlobal(SBID) ) {
                    $Tpl->add('blog#insert', array(
                        'url'   => acmsLink(array(
                            'admin' => 'blog_edit',
                            'alt'   => 'insert',
                        )),
                        'stay'  => $this->linkCheck('blog'),
                    ));
                }
            }
        }
    }

    function normalAuth(& $Tpl)
    {
        $Tpl->add('dashboard', array(
            'url'   => acmsLink(array('admin' => 'top', 'bid' => BID)),
            'stay'  => $this->linkCheck('top'),
        ));

        if (editionWithProfessional()) {
            $approval = array(
                'url'   => acmsLink(array('admin' => 'approval_notification', 'bid' => BID)),
                'stay'  => $this->linkCheck('approval_notification'),
            );
            if ( $badge = Approval::notificationCount() ) {
                $approval['badge'] = $badge;
            }
            $Tpl->add('approval#notification', $approval);
        }

        //--------------
        // contribution
        if ( sessionWithContribution() ) {
            $Tpl->add('entry#index', array(
                'url'   => acmsLink(array('admin' => 'entry_index', 'bid' => BID)),
                'stay'  => $this->linkCheck('entry_index'),
            ));
            $Tpl->add('entry#trash', array(
                'url'   => acmsLink(array('admin' => 'entry_trash', 'bid' => BID)),
                'stay'  => $this->linkCheck('entry_trash'),
            ));
            if ( config('media_library') === 'on' ) {
                $Tpl->add('media#index', array(
                    'url'   => acmsLink(array('bid' => BID, 'admin' => 'media_index')),
                    'stay'  => $this->linkCheck('media'),
                ));
            }
            if ( IS_LICENSED ) $Tpl->add('entry#insert');

            //--------------
            // compilation
            if ( sessionWithCompilation() ) {
                $Tpl->add('category#index', array(
                    'url'   => acmsLink(array('admin' => 'category_index', 'bid' => BID)),
                    'stay'  => $this->linkCheck('category'),
                ));
                $Tpl->add('tag', array(
                    'url'   => acmsLink(array('admin' => 'tag_index', 'bid' => BID)),
                    'stay'  => $this->linkCheck('tag'),
                ));
                $Tpl->add('comment', array(
                    'url'   => acmsLink(array('bid' => BID, 'admin' => 'comment_index')),
                    'stay'  => $this->linkCheck('comment'),
                ));
                $Tpl->add('trackback', array(
                    'url'   => acmsLink(array('bid' => BID, 'admin' => 'trackback_index')),
                    'stay'  => $this->linkCheck('trackback'),
                ));
                if ( IS_LICENSED ) {
                    $Tpl->add('category#insert', array(
                        'url'   => acmsLink(array('admin' => 'category_edit', 'bid' => BID)),
                        'stay'  => $this->linkCheck('category'),
                    ));
                }
                if ( config('media_library') === 'on' ) {
                    $Tpl->add('media#index', array(
                        'url'   => acmsLink(array('bid' => BID, 'admin' => 'media_index')),
                        'stay'  => $this->linkCheck('media'),
                    ));
                }
                $Tpl->add('form#index', array(
                    'url'   => acmsLink(array('bid' => BID, 'admin' => 'form_index')),
                    'stay'  => $this->linkCheck('form'),
                ));
                $Tpl->add('schedule#index', array(
                    'url'   => acmsLink(array('bid' => BID, 'admin' => 'schedule_index')),
                    'stay'  => $this->linkCheck('schedule'),
                ));

                //----------------
                // administration
                if ( sessionWithAdministration() ) {
                    $Tpl->add('blog#index', array(
                        'url'   => acmsLink(array('admin' => 'blog_index', 'bid' => BID)),
                        'stay'  => $this->linkCheck('blog_index'),
                    ));
                    $Tpl->add('blog#edit', array(
                        'url'   => acmsLink(array('admin' => 'blog_edit', 'bid' => BID)),
                        'stay'  => $this->linkCheck('blog_edit'),
                    ));
                    $Tpl->add('alias#index', array(
                        'url'   => acmsLink(array('admin' => 'alias_index', 'bid' => BID)),
                        'stay'  => $this->linkCheck('alias'),
                    ));
                    $Tpl->add('user#index', array(
                        'url'   => acmsLink(array('admin' => 'user_index', 'bid' => BID)),
                        'stay'  => $this->linkCheck('user'),
                    ));
                    $Tpl->add('rule#index', array(
                        'url'   => acmsLink(array('admin' => 'rule_index', 'bid' => BID)),
                        'stay'  => $this->linkCheck('rule'),
                    ));
                    $Tpl->add('module#index', array(
                        'url'   => acmsLink(array('admin' => 'module_index', 'bid' => BID)),
                        'stay'  => $this->linkCheck('module'),
                    ));
                    $Tpl->add('shortcut#index', array(
                        'url'   => acmsLink(array('admin' => 'shortcut_index', 'bid' => BID)),
                        'stay'  => $this->linkCheck('shortcut'),
                    ));
                    $Tpl->add('publish#index', array(
                        'url'   => acmsLink(array('bid' => BID, 'admin' => 'publish_index')),
                        'stay'  => $this->linkCheck('publish'),
                    ));
                    $Tpl->add('backup#index', array(
                        'url'   => acmsLink(array('bid' => BID, 'admin' => 'backup_index')),
                        'stay'  => $this->linkCheck('backup'),
                    ));
                    $Tpl->add('import#index', array(
                        'url'   => acmsLink(array('bid' => BID, 'admin' => 'import_index')),
                        'stay'  => $this->linkCheck('import'),
                    ));
                    $Tpl->add('app#index', array(
                        'url'   => acmsLink(array('bid' => BID, 'admin' => 'app_index')),
                        'stay'  => $this->linkCheck('app_index'),
                    ));
                    $Tpl->add('checklist', array(
                        'url'   => acmsLink(array('bid' => BID, 'admin' => 'checklist')),
                        'stay'  => $this->linkCheck('checklist'),
                    ));
                    $Tpl->add('cart#menu', array(
                        'url'   => acmsLink(array('bid' => BID, 'admin' => 'cart_menu')),
                        'stay'  => $this->linkCheck('cart_menu'),
                    ));
                    $Tpl->add('fix#index', array(
                        'url'   => acmsLink(array('admin' => 'fix_index', 'bid' => BID)),
                        'stay'  => $this->linkCheck('fix'),
                    ));

                    if ( defined('LICENSE_PLUGIN_SHOP_PRO') and LICENSE_PLUGIN_SHOP_PRO ) {
                        $Tpl->add('shop#menu', array(
                            'url'   => acmsLink(array('bid' => BID, 'admin' => 'shop_menu')),
                            'stay'  => $this->linkCheck('shop'),
                        ));
                    }

                    if ( IS_LICENSED ) {
                        $Tpl->add('user#insert', array(
                            'url'   => acmsLink(array('admin' => 'user_edit', 'bid' => BID)),
                            'stay'  => $this->linkCheck('user#insert'),
                        ));
                        $Tpl->add('config#set_index', array(
                            'url'   => acmsLink(array('admin' => 'config_set_index', 'bid' => BID)),
                            'stay'  => $this->linkCheck('config'),
                        ));
                        $Tpl->add('rule#insert', array(
                            'url'   => acmsLink(array('admin' => 'rule_edit', 'bid' => BID)),
                            'stay'  => $this->linkCheck('rule'),
                        ));
                        $Tpl->add('module#insert', array(
                            'url'   => acmsLink(array('admin' => 'module_edit', 'bid' => BID)),
                            'stay'  => $this->linkCheck('module'),
                        ));
                    }
                    if ( IS_LICENSED ) {
                        if ( isBlogGlobal(SBID) ) {
                            $Tpl->add('blog#insert', array(
                                'url'   => acmsLink(array(
                                    'admin' => 'blog_edit',
                                    'alt'   => 'insert',
                                )),
                                'stay'  => $this->linkCheck('blog'),
                            ));
                        }
                    }
                }
            }
        }
    }

    function get()
    {
        if ( !sessionWithSubscription(BID, false) ) {
            page404();
        }
        $Tpl = new Template($this->tpl, new ACMS_Corrector());

        //--------
        // update
        if ( 1
            && BID === RBID
            && sessionWithAdministration()
            && config('system_update_range') !== 'none'
        ) {
            $update = 0;
            $checkUpdateService = App::make('update.check');
            $range = CheckForUpdate::PATCH_VERSION;
            if ( config('system_update_range') === 'minor' ) {
                $range = CheckForUpdate::MINOR_VERSION;
            }
            if ( $checkUpdateService->checkUseCache(phpversion(), $range) ) {
                $update = 1;
            }
            $Tpl->add('update', array(
                'url' => acmsLink(array('admin' => 'update', 'bid' => BID)),
                'stay' => $this->linkCheck('update'),
                'update' => $update,
            ));
        }

        //--------------
        // subscription
        if ( IS_LICENSED ) {
            $Tpl->add('user#profile', array(
                'url'   => acmsLink(array(
                    'bid'   => SBID,
                    'uid'   => SUID,
                    'admin' => 'user_edit',
                )),
                'icon'  => loadUserIcon(SUID),
            ));
        }

        //------------
        // enterprise
        if ( sessionWithEnterpriseAdministration() ) {
            if ( BID == RBID ) {
                $Tpl->add('role#index', array(
                    'url'   => acmsLink(array('admin' => 'role_index', 'bid' => RBID)),
                    'stay'  => $this->linkCheck('role'),
                ));
                $Tpl->add('usergroup#index', array(
                    'url'   => acmsLink(array('admin' => 'usergroup_index', 'bid' => RBID)),
                    'stay'  => $this->linkCheck('usergroup'),
                ));
                if (enableApproval()) {
                    $Tpl->add('approval#index', array(
                        'url'   => acmsLink(array('admin' => 'approval_index', 'bid' => RBID)),
                        'stay'  => $this->linkCheck('approval_index'),
                    ));
                }
            }
        }

        //--------------
        // professional
        if ( 1
            && !sessionWithEnterpriseAdministration()
            && sessionWithProfessionalAdministration()
            && BID == RBID
        ) {
            $Tpl->add('approval#index', array(
                'url'   => acmsLink(array('admin' => 'approval_index', 'bid' => RBID)),
                'stay'  => $this->linkCheck('approval_index'),
            ));
        }

        //----------------------------
        // professional or enterprise
        if (sessionWithProfessionalAdministration() || sessionWithEnterpriseAdministration()) {
            $Tpl->add('static_export#index', array(
                'url'   => acmsLink(array('admin' => 'static-export_index', 'bid' => BID)),
                'stay'  => $this->linkCheck('static-export_index'),
            ));
        }

        if ( roleAvailableUser() ) {
            $this->roleAuth($Tpl);
        } else {
            $this->normalAuth($Tpl);
        }

        return $Tpl->get();
    }
}
