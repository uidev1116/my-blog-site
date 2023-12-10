<?php

use Acms\Services\Facades\RichEditor;

class ACMS_GET_Admin_Config extends ACMS_GET_Admin
{
    public function & getConfig($rid, $mid, $setid = null)
    {
        $post_config =& $this->Post->getChild('config');

        $config = Config::loadDefaultField();
        if ($setid) {
            $config->overload(Config::loadConfigSet($setid));
        } else {
            $config->overload(Config::loadBlogConfig(BID));
        }
        $_config = null;

        if (!!$rid && !$mid) {
            $_config = Config::loadRuleConfig($rid, $setid);
        } elseif (!!$mid) {
            $_config = Config::loadModuleConfig($mid, $rid);
        }

        if (!!$_config) {
            $config->overload($_config);
            foreach (
                array(
                    'links_label',
                    'links_value',
                    'navigation_label',
                    'navigation_uri',
                    'navigation_attr',
                    'navigation_a_attr',
                    'navigation_parent',
                    'navigation_target',
                    'navigation_publish',
                ) as $fd
            ) {
                $config->setField($fd, $_config->getArray($fd));
            }
        }
        $config->set('session_cookie_lifetime', env('SESSION_COOKIE_LIFETIME', 259200));

        if (!$post_config->isNull() && ADMIN !== 'config_unit') {
            $config->overload($post_config);
            $post_config->overload($config);
            return $post_config;
        }

        return $config;
    }

    public function get()
    {
        if (!IS_LICENSED) {
            return '';
        }
        if (!($rid = intval($this->Get->get('rid')))) {
            $rid = null;
        }
        if (!($mid = intval($this->Get->get('mid')))) {
            $mid = null;
        }
        if (!($setid = intval($this->Get->get('setid')))) {
            $setid = null;
        }
        if ($mid) {
            $setid = null;
        }

        if (!Config::isOperable($rid, $mid, $setid)) {
            die403();
        }

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $vars   = array();
        $Config =& $this->getConfig($rid, $mid, $setid);

        if (!$this->Post->isValidAll()) {
            $Tpl->add('msg#error');
        }

        // add alert email info
        $Config->setField('alert_email_from', env('ALERT_EMAIL_FROM'));
        $Config->setField('alert_email_to', env('ALERT_EMAIL_TO'));
        $Config->setField('alert_email_bcc', env('ALERT_EMAIL_BCC'));

        //----------------
        // file extension
        $Config->setField('file_extension_document@list'
            , join(', ', $Config->getArray('file_extension_document'))
        );
        $Config->setField('file_extension_document');
        $Config->setField('file_extension_archive@list'
            , join(', ', $Config->getArray('file_extension_archive'))
        );
        $Config->setField('file_extension_archive');
        $Config->setField('file_extension_movie@list'
            , join(', ', $Config->getArray('file_extension_movie'))
        );
        $Config->setField('file_extension_movie');
        $Config->setField('file_extension_audio@list'
            , join(', ', $Config->getArray('file_extension_audio'))
        );
        $Config->setField('file_extension_audio');

        $admin  = ADMIN;
        if ( $mid ) {
            $module = loadModule($mid);
            $admin  = 'config_'.strtolower(preg_replace('@(?<=[a-zA-Z0-9])([A-Z])@', '-$1', $module->get('name')));
        }

        $vars['shortcutUrl'] = acmsLink(array(
                'bid'   => BID,
                'admin' => 'shortcut_edit',
                'query' => array(
                    'admin'  => $admin,
                    'rid'   => $rid,
                    'mid'   => $mid,
                    'setid' => $setid
                )
        ));

        $vars += $this->buildColumn($Config, $Tpl);

        //-----------------
        // image unit size
        // Configを変質させてしまうので、Admin_Entry_Editとは同居できない
        // buildColumnメソッド内で、column_image_sizeを利用しているので、
        // この処理より後に、buildColumnメソッドは実行できない。
        if ( $sizes = $Config->getArray('column_image_size') ) {
            foreach ( $sizes as $i => $size ) {
                $sizes[$i] = preg_replace('/([^\d]*)/', '', $size);
            }
            $Config->set('column_image_size', $sizes);
        }
        if ( $large_size = $Config->get('image_size_large') ) {
            $Config->set('image_size_large', preg_replace('/([^\d]*)/', '', $large_size));
        }
        if ( $tiny_size = $Config->get('image_size_tiny') ) {
            $Config->set('image_size_tiny', preg_replace('/([^\d]*)/', '', $tiny_size));
        }

        $vars   += $this->buildNavigation($Config, $Tpl);
        $vars   += $this->buildField($Config, $Tpl, array(), 'config');

        $vars['notice_mess'] = $this->Post->get('notice_mess');

        // 一覧ページ
        if ( !$rid = idval($this->Get->get('rid')) ) $rid = null;
        if ( !$mid = idval($this->Get->get('mid')) ) $mid = null;
        if ( !$setid = idval($this->Get->get('setid')) ) $setid = null;

        $vars['indexUrl']   = $this->getIndexUrl($rid, $mid, $setid);

        $this->extendTemplate($vars, $Tpl);
        $Tpl->add(null, $vars);

        return $Tpl->get();
    }

    function extendTemplate(&$vars, &$Tpl)
    {

    }

    function getIndexUrl($rid, $mid, $setid)
    {
        $url = '';
        if ( sessionWithAdministration() ) {
            if ($mid) {
                $url    = acmsLink(array(
                    'bid'   => BID,
                    'admin' => 'module_index',
                ));
            } else if ($rid || $setid) {
                $url    = acmsLink(array(
                    'bid'   => BID,
                    'admin' => 'config_index',
                    'query' => array(
                        'rid' => $rid,
                        'setid' => $setid,
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
        return $url;
    }

    function bulidImageSize()
    {

    }

    function buildColumn(& $Config, & $Tpl, $rootBlock=array())
    {
        if ( !is_array($rootBlock) ) $rootBlock = array($rootBlock);
        array_unshift($rootBlock, 'Config_Column');

        // typeで参照できるラベルの連想配列
        $aryTypeLabel    = array();
        foreach ( $Config->getArray('column_add_type') as $i => $type ) {
            $aryTypeLabel[$type]    = $Config->get('column_add_type_label', '', $i);
        }

        $labels = $Config->getArray('column_add_type_label');
        $column = array('insert' => '新規エントリー作成');
        foreach ( $Config->getArray('column_add_type') as $mode ) {
            $label = array_shift($labels);
            if ( preg_match('@^(text|table|rich-editor|image|file|osmap|map|video|youtube|eximage|break|quote|media|module|custom)[^_]+(.*)@', $mode) ) continue;
            $column['add_'.$mode] = $label;
        }
        foreach ( $column as $mode => $modeLabel ) {
            $pfx        = 'column_def_'.$mode.'_';
            $aryType    = $Config->getArray($pfx.'type');
            $aryAlign   = $Config->getArray($pfx.'align', true);
            $aryGroup   = $Config->getArray($pfx.'group', true);
            $aryClass   = $Config->getArray($pfx.'class', true);
            $arySize    = $Config->getArray($pfx.'size', true);
            $aryEdit    = $Config->getArray($pfx.'edit', true);
            $aryAttr    = $Config->getArray($pfx.'attr', true);
            $aryAAttr   = $Config->getArray($pfx.'a_attr', true);
            $aryFd1     = $Config->getArray($pfx.'field_1', true);
            $aryFd2     = $Config->getArray($pfx.'field_2', true);
            $aryFd3     = $Config->getArray($pfx.'field_3', true);
            $aryFd4     = $Config->getArray($pfx.'field_4', true);
            $aryFd5     = $Config->getArray($pfx.'field_5', true);

            foreach ( $aryType as $i => $type ) {

                $Field  = new Field();
                $Field->setField('pfx', $pfx);
                $Field->setField('align', ite($aryAlign, $i));
                $Field->setField('group', ite($aryGroup, $i));
                $Field->setField('class', ite($aryClass, $i));
                $Field->setField('size', ite($arySize, $i));
                $Field->setField('edit', ite($aryEdit, $i));
                $Field->setField('attr', ite($aryAttr, $i));
                $Field->setField('a_attr', ite($aryAAttr, $i));
                $Field->setField('field_1', ite($aryFd1, $i));
                $Field->setField('field_2', ite($aryFd2, $i));
                $Field->setField('field_3', ite($aryFd3, $i));
                $Field->setField('field_4', ite($aryFd4, $i));
                $Field->setField('field_5', ite($aryFd5, $i));

                // 特定指定子を含むユニットタイプ
                $actualType = $type;
                // 特定指定子を除外した、一般名のユニット種別
                $type = detectUnitTypeSpecifier($type);


                if ( 'text' == $type ) {
                    foreach ( $Config->getArray('column_text_tag') as $j => $tag ) {
                        $_vars = array(
                            'value' => $tag,
                            'label' => $Config->get('column_text_tag_label', '', $j),
                        );
                        if ( $Field->get('field_2') == $tag ) {
                            $_vars['selected'] = $Config->get('attr_selected');
                        }
                        $Tpl->add(array_merge(array('textTag:loop', $type), $rootBlock), $_vars);
                    }
                } else if ( 'table' == $type ) {

                } else if ( 'image' == $type ) {
                    foreach ( $Config->getArray('column_image_size') as $j => $size ) {
                        $_vars  = array(
                            'value' => $size,
                            'label' => $Config->get('column_image_size_label', '', $j),
                        );
                        if ( $Field->get('size') == $size ) {
                            $_vars['selected']  = $Config->get('attr_selected');
                        }
                        $Tpl->add(array_merge(array('size:loop', $type), $rootBlock), $_vars);
                    }
                } else if ( 'osmap' == $type || 'map' == $type ) {
                    foreach ( $Config->getArray('column_map_size') as $j => $size ) {
                        $_vars  = array(
                            'value' => $size,
                            'label' => $Config->get('column_map_size_label', '', $j),
                        );
                        if ( $Field->get('size') == $size ) {
                            $_vars['selected']  = $Config->get('attr_selected');
                        }
                        $Tpl->add(array_merge(array('size:loop', $type), $rootBlock), $_vars);
                    }
                } else if ( 'youtube' == $type ) {
                    foreach ( $Config->getArray('column_youtube_size') as $j => $size ) {
                        $_vars  = array(
                            'value' => $size,
                            'label' => $Config->get('column_youtube_size_label', '', $j),
                        );
                        if ( $Field->get('size') == $size ) {
                            $_vars['selected']  = $Config->get('attr_selected');
                        }
                        $Tpl->add(array_merge(array('size:loop', $type), $rootBlock), $_vars);
                    }
                } else if ( 'video' == $type ) {
                    foreach ( $Config->getArray('column_video_size') as $j => $size ) {
                        $_vars  = array(
                            'value' => $size,
                            'label' => $Config->get('column_video_size_label', '', $j),
                        );
                        if ( $Field->get('size') == $size ) {
                            $_vars['selected']  = $Config->get('attr_selected');
                        }
                        $Tpl->add(array_merge(array('size:loop', $type), $rootBlock), $_vars);
                    }
                } else if ( 'eximage' == $type ) {
                    foreach ( $Config->getArray('column_eximage_size') as $j => $size ) {
                        $_vars  = array(
                            'value' => $size,
                            'label' => $Config->get('column_eximage_size_label', '', $j),
                        );
                        if ( $Field->get('size') == $size ) {
                            $_vars['selected']  = $Config->get('attr_selected');
                        }
                        $Tpl->add(array_merge(array('size:loop', $type), $rootBlock), $_vars);
                    }
                } else if ( 'quote' == $type ) {

                } else if ( 'rich-editor' == $type ) {
                    $Tpl->add(array_merge(array('edit', $type), $rootBlock), array(
                        'html' => RichEditor::render($Field->get('field_1'))
                    ));
                } else if ( 'media' == $type ) {
                    foreach ($Config->getArray('column_media_size') as $j => $size) {
                        $_vars  = array(
                            'value' => $size,
                            'label' => $Config->get('column_media_size_label', '', $j),
                        );
                        if ($Field->get('size') == $size) {
                            $_vars['selected']  = $Config->get('attr_selected');
                        }
                        $Tpl->add(array_merge(array('size:loop', $type), $rootBlock), $_vars);
                    }
                }

                //-------
                // group
                if ( 1
                    && 'on' === $Config->get('unit_group')
                    && !preg_match('/^(break|module|custom)$/', $type)
                ) {
                    $classes = $Config->getArray('unit_group_class');
                    $labels  = $Config->getArray('unit_group_label');

                    if ( count($classes) === count($labels) ) {
                        foreach ( $labels as $k => $label ) {
                            $Tpl->add(array_merge(array('group:loop', 'group:veil', $type), $rootBlock), array(
                                 'group.value'     => $classes[$k],
                                 'group.label'     => $label,
                                 'group.selected'  => ($classes[$k] === $Field->get('group')) ? $Config->get('attr_selected') : '',
                            ));
                        }

                        $Tpl->add(array_merge(array('group:veil', $type), $rootBlock), array(
                            'group.pfx' => $Field->get('pfx'),
                        ));
                    }
                }
                // selected用のgroupをunset ( しないと，以後のbuildFieldでgroup:loopが暴発する )
                $Field->delete('group');

                $Field->setField('size');
                $vars   = $this->buildField($Field, $Tpl, array_merge(array($type), $rootBlock));

                if( isset( $aryTypeLabel[$actualType] ) ){
                    $vars  += array(
                        'actualType'  => $actualType,
                        'actualLabel' => $aryTypeLabel[$actualType],
                    );
                }
                $Tpl->add(array_merge(array($type, 'loop', 'mode:loop'), $rootBlock), $vars);
                $Tpl->add(array_merge(array('loop', 'mode:loop'), $rootBlock));
            }

            foreach ( $Config->getArray('column_add_type') as $i => $type ) {
                if ( !preg_match('/^(text|table|rich-editor|image|file|osmap|map|video|youtube|eximage|break|quote|media|module|custom)($|_)/', $type) ) {
                    continue;
                }
                $Tpl->add(array_merge(array('add_type:loop', 'mode:loop'), $rootBlock), array(
                    'type'  => $type,
                    'label' => $Config->get('column_add_type_label', '未定義', $i),
                    'modePrefix.type' => $pfx
                ));
            }
            $Tpl->add(array_merge(array('mode:loop'), $rootBlock), array(
                'mode'          => $modeLabel,
                'modePrefix'    => $pfx,
            ));
        }
        $Tpl->add($rootBlock);

        return array();
    }

    function buildNavigation(& $Config, & $Tpl, $rootBlock=array())
    {
        if ( !is_array($rootBlock) ) $rootBlock = array($rootBlock);
        array_unshift($rootBlock, 'Config_Navigation');
        $addNum = 0;

        $Count  = array(0=>$addNum);
        $Parent = array(0=>array());
        foreach ( $Config->getArray('navigation_label') as $i => $label ) {
            $id         = $i + 1;
            $pid        = intval($Config->get('navigation_parent', 0, $i));
            $Parent[$pid][$id]   = array(
                'id'        => $id,
                'pid'       => $pid,
                'label'     => $label,
                'uri'       => $Config->get('navigation_uri', null, $i),
                'target'    => $Config->get('navigation_target', null, $i),
                'publish'   => $Config->get('navigation_publish', null, $i),
                'attr'      => $Config->get('navigation_attr', null, $i),
                'a_attr'    => $Config->get('navigation_a_attr', null, $i),
                'marks'     => array(),
            );
            $Count[$pid]    = (isset($Count[$pid]) ? $Count[$pid] : 0) + 1;
        }

        $all        = array();
        $pidStack   = array(0);
        $aryMark    = array('');
        while ( count($pidStack) ) {
            $pid    = array_pop($pidStack);
            $mark   = array_pop($aryMark);
            while ( $row = array_shift($Parent[$pid]) ) {
                $id = $row['id'];

                $row['marks']   = array_merge(array(count($Parent[$pid]) ? 1 : 0), $aryMark);
                $all[] = $row;

                if ( isset($Parent[$id]) ) {
                    if ( count($Parent[$pid]) ) {
                        $aryMark[] = 3;
                    } else {
                        $aryMark[] = 2;
                    }
                    $aryMark[] = $mark;
                    $pidStack[] = $pid;
                    $pidStack[] = $id;
                    break;
                }
            }
        }

        //---------------
        // parent select
        $PSelect    = array();
        foreach ( $all as $row ) {
            $label  = $row['label'];

            //--------
            // indent
            $mark   = '';
            $marks  = array_reverse($row['marks']);
            $cnt    = count($row['marks']);
            for ( $i=1; $i<$cnt; $i++ ) {
                if ( !isset($marks[$i]) ) continue;
                $mark   .= $Config->get('indent_marks', '', $marks[$i]);
            }

            $PSelect[$row['id']]    = $mark.htmlspecialchars($label);
        }

        $seq    = 0;
        $Sort   = array();
        $length = count($all) - 1;
        foreach ( $all as $row ) {

            $id     = $row['id'];
            $pid    = $row['pid'];
            $marks  = $row['marks'];

            $Sort[$pid] = (isset($Sort[$pid]) ? $Sort[$pid] : 0) + 1;

            //--------
            // indent
            $level  = 0;
            $marks  = array_reverse($marks);
            foreach ( $marks as $i => $mark ) {
                if ( empty($i) ) continue;
                if ( 0 == $mark ) {
                    $block  = 'child#last';
                } else if ( 1 == $mark ) {
                    $block  = 'child';
                } else if ( 2 == $mark ) {
                    $block  = 'descendant#last';
                } else if ( 3 == $mark ) {
                    $block  = 'descendant';
                } else {
                    continue;
                }
                $Tpl->add(array_merge(array($block, 'navigation:loop'), $rootBlock));
                $level++;
            }

            //------
            // sort
            for ( $i=1; $i<=$Count[$pid]; $i++ ) {
                $vars   = array(
                    'label' => $i,
                    'value' => $i,
                );
                if ( $i == $Sort[$pid] ) {
                    $vars['selected'] = $Config->get('attr_selected');
                }
                $Tpl->add(array_merge(array('sort:loop', 'navigation:loop'), $rootBlock), $vars);
            }

            //---------------
            // parent select
            foreach ( $PSelect as $_id => $_label ) {
                $vars   = array(
                    'value' => $_id,
                    'label' => $_label,
                );
                if ( $pid == $_id ) $vars['selected']   = $Config->get('attr_selected');
                $Tpl->add(array_merge(array('parent:loop', 'navigation:loop'), $rootBlock), $vars);
            }

            $vars   = array(
                'seq'   => $seq,
                'level' => $level,
                'pseq'  => $pid,
                'label' => $row['label'],
                'uri'   => $row['uri'],
                'attr'  => $row['attr'],
                'a_attr' => $row['a_attr'],
                'navigation_target:checked#'.$row['target'] => $Config->get('attr_checked'),
                'navigation_publish:checked#'.$row['publish'] => $Config->get('attr_checked'),
            );
            if ($length !== $seq) {
                $Tpl->add('glue');
            }
            $Tpl->add(array_merge(array('navigation:loop'), $rootBlock), $vars);
            $seq++;
        }
        $Tpl->add($rootBlock);

        return array();
    }
}
