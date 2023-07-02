<?php

class ACMS_GET_Admin_Topicpath extends ACMS_GET_Admin
{
    function get()
    {
        if ( !SUID ) return '';

        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        $blogs = array();

        //-----------
        // blog tree
        $DB = DB::singleton(dsn());

        $SQL = SQL::newSelect('blog');
        $SQL->addSelect('blog_id');
        $SQL->addSelect('blog_name');
        $SQL->addSelect('blog_parent');
        $SQL->addWhereIn('blog_id', Auth::getAuthorizedBlog(SUID));
        $SQL->setOrder('blog_left', 'ASC');
        $all = $DB->query($SQL->get(dsn()), 'all');

        foreach ($all as $blog) {
            $pbid = $blog['blog_parent'];
            if (!isset($blogs[$pbid])) {
                $blogs[$pbid] = array();
            }
            $blogs[$pbid][] = $blog;
        }

        $SQL = SQL::newSelect('blog');
        $SQL->addSelect('blog_id');
        $SQL->addSelect('blog_name');

        $fromLeft   = ACMS_RAM::blogLeft(BID);
        $fromRight  = ACMS_RAM::blogRight(BID);
        $toLeft     = ACMS_RAM::blogLeft(SBID);
        $toRight    = ACMS_RAM::blogRight(SBID);
        $SQL->addWhereBw('blog_left', $toLeft, $fromLeft);
        $SQL->addWhereBw('blog_right', $fromRight, $toRight);
        $SQL->setOrder('blog_left');
        $q  = $SQL->get(dsn());
        $DB->query($q, 'fetch');
        $i  = 0;
        while ( $row = $DB->fetch($q) ) {
            $bid    = intval($row['blog_id']);
            if ( !empty($i) ) $Tpl->add('glue');

            $topics = array();
            if (isset($blogs[$bid]) && count($blogs[$bid]) > 0) {
                $topics['child_blog'] = 1;
                foreach ($blogs[$bid] as $child) {
                    $Tpl->add(array('childBlog:loop', 'topic:loop'), array(
                        'name' => $child['blog_name'],
                        'blogUrl' => acmsLink(array(
                            'bid'   => $child['blog_id'],
                            'admin' => 'top'
                        )),
                    ));
                }
            }
            $topics += array(
                'url'   => acmsLink(array(
                    'bid'   => $bid,
                    'admin' => 'top'
                )),
                'label'  => $row['blog_name'],
            );

            $Tpl->add('topic:loop', $topics);
            $i++;
        }

        $aryAdmin   = array();
        if ( 'form_log' == ADMIN ) {
            $aryAdmin[] = 'form_index';
            $aryAdmin[] = 'form_edit';
            $aryAdmin[] = 'form_log';
        } else if ( 'shop' == substr(ADMIN, 0, strlen('shop')) )  {
            if ( 'shop_menu' != ADMIN ) $aryAdmin[] = 'shop_menu';
            if ( preg_match('@_edit$@', ADMIN) ) {
                $aryAdmin[] = str_replace('_edit', '_index', ADMIN);
            }
            $aryAdmin[] = ADMIN;
        } else if ( 'schedule' == substr(ADMIN, 0, strlen('schedule')) ) {
            if ('schedule_index' != ADMIN) {
                $aryAdmin[] = 'schedule_index';
            }
            $aryAdmin[] = ADMIN;
        } else if ('config_set' == substr(ADMIN, 0, strlen('config_set'))) {
            $aryAdmin[] = 'config_set_index';
            $aryAdmin[] = 'rule_index';
            $aryAdmin[] = 'rule_edit';
        } else if ('config' == substr(ADMIN, 0, strlen('config'))) {
            $aryAdmin[] = 'config_set_index';
            if (!in_array(ADMIN, array('config_set_index', 'config_set_edit'))) {
                if ('config_import' !== ADMIN && 'config_export' !== ADMIN) {
                    $aryAdmin[] = 'config_index';
                    $aryAdmin[] = 'rule_index';
                    $aryAdmin[] = 'rule_edit';
                }
                if ('config_index' !== ADMIN) {
                    $aryAdmin[] = ADMIN;
                }
            }
            if ('config_set_edit' === ADMIN) {
                $aryAdmin[] = ADMIN;
            }
        } else if ('module' == substr(ADMIN, 0, strlen('module'))) {
            $aryAdmin[] = 'module_index';
            if ('module_import' !== ADMIN ) {
                $aryAdmin[] = 'rule_index';
                $aryAdmin[] = 'rule_edit';
            }
            if ( 'module_index' !== ADMIN ) {
                $aryAdmin[] = ADMIN;
            }
        } else if ( 'fix' == substr(ADMIN, 0, strlen('fix')) ) {

            $aryAdmin[] = 'fix_index';
            if ( 'fix_index' <> ADMIN  ) {
                $aryAdmin[] = ADMIN;
            }
        } else if ( preg_match('@(\_edit|\_editor)$@', ADMIN) ) {
            if ( !('user_edit' == ADMIN and !sessionWithContribution()) ) {
                if ( 'blog_edit' !== ADMIN ) {
                    $aryAdmin[] = str_replace(array('_editor', '_edit'), array('_index', '_index'), ADMIN);
                }
            }
            $aryAdmin[] = ADMIN;
        } else if ( 'import' == substr(ADMIN, 0, strlen('import')) ) {
            if ( 'import_index' != ADMIN ) $aryAdmin[] = 'import_index';
            $aryAdmin[] = ADMIN;
        } else if ( ADMIN !== 'top' ) {
            $aryAdmin[] = ADMIN;
        }

        foreach ( $aryAdmin as $admin ) {
            $Tpl->add('glue');
            $Tpl->add($admin);
            if ( preg_match('@_edit$@', $admin) ) {
                $url = acmsLink(array(
                    'bid' => BID,
                    'uid' => UID,
                    'cid' => CID,
                    'eid' => EID,
                    'tag' => TAG,
                    'admin' => $admin,
                    'query' => Field::singleton('get'),
                ));
            } else if ($admin === 'config_set_default' || $admin === 'config_index' || $admin === 'rule_edit') {
                $url = acmsLink(array(
                    'bid' => BID,
                    'admin' => ADMIN,
                    'query' => array(
                        'rid' => $this->Get->get('rid'),
                        'setid' => $this->Get->get('setid'),
                    ),
                ));
            } else {
                $url = acmsLink(array(
                    'bid'   => BID,
                    'admin' => $admin,
                    'query' => array(
                        'rid'   => $this->Get->get('rid'),
                        'mid'   => $this->Get->get('mid'),
                        'fmid'  => $this->Get->get('fmid'),
                    ),
                ));
            }
            $topicVars = array('url' => $url);

            if ($admin === 'config_set_default' || $admin === 'config_index') {
                if ($configSet = $this->getConfigSet()) {
                    $topicVars['config_set'] = 1;
                    foreach ($configSet as $set) {
                        $Tpl->add(array('configSet:loop', 'topic:loop'), array(
                            'name' => $set['name'],
                            'configSetUrl' => acmsLink(array(
                                'bid' => $set['bid'],
                                'admin' => ADMIN,
                                'query' => array(
                                    'rid' => $this->Get->get('rid'),
                                    'setid' => $set['id'],
                                )
                            )),
                        ));
                    }
                }
            }
            if ($admin === 'rule_edit') {
                if ($rules = $this->getRule()) {
                    $topicVars['rule'] = 1;
                    foreach ($rules as $rule) {
                        $Tpl->add(array('rule:loop', 'topic:loop'), array(
                            'name' => $rule['name'],
                            'ruleUrl' => acmsLink(array(
                                'bid' => $rule['bid'],
                                'admin' => ADMIN,
                                'query' => array(
                                    'setid' => $this->Get->get('setid'),
                                    'mid' => $this->Get->get('mid'),
                                    'rid' => $rule['id'],
                                )
                            )),
                        ));
                    }
                }
            }
            $Tpl->add('topic:loop', $topicVars);
        }
        $rootConfig = Config::loadBlogConfigSet(RBID);
        $Tpl->add(null, array(
            'blog_theme_logo@squarePath' => $rootConfig->get('blog_theme_logo@squarePath'),
        ));

        return $Tpl->get();
    }

    protected function getConfigSet() {
        $SQL = SQL::newSelect('config_set');
        $SQL->addWhereOpr('config_set_blog_id', BID);
        $SQL->setOrder('config_set_sort', 'ASC');

        $result = array();
        $result[] = array(
            'id' => null,
            'bid' => BID,
            'name' => gettext('このブログの初期コンフィグ'),
        );

        $all = DB::query($SQL->get(dsn()), 'all');
        foreach ($all as $item) {
            $result[] = array(
                'id' => $item['config_set_id'],
                'bid' => $item['config_set_blog_id'],
                'name' => $item['config_set_name'],
            );
        }
        return $result;
    }

    protected function getRule() {
        $SQL = SQL::newSelect('rule');
        $SQL->addLeftJoin('blog', 'blog_id', 'rule_blog_id');
        ACMS_Filter::blogTree($SQL, BID, 'ancestor-or-self');

        $Where = SQL::newWhere();
        $Where->addWhereOpr('rule_blog_id', BID, '=', 'OR');
        $Where->addWhereOpr('rule_scope', 'global', '=', 'OR');
        $SQL->addWhere($Where);
        $SQL->addWhereOpr('rule_status', 'open');
        $SQL->setOrder('rule_sort');

        $result = array();
        $result[] = array(
            'id' => null,
            'bid' => BID,
            'name' => gettext('ルールなし'),
        );

        $all = DB::query($SQL->get(dsn()), 'all');
        foreach ($all as $item) {
            $result[] = array(
                'id' => $item['rule_id'],
                'bid' => BID,
                'name' => $item['rule_name'],
            );
        }
        return $result;
    }
}
