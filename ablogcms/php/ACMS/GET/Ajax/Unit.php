<?php

class ACMS_GET_Ajax_Unit extends ACMS_GET
{
    function get()
    {
        if (!($column = $this->Get->get('column'))) {
            return false;
        }
        list($pfx, $type)   = explode('-', $column, 2);

        //--------------
        // Config Data
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
        $Config = Config::loadDefaultField();
        if ($setid) {
            $Config->overload(Config::loadConfigSet($setid));
        } else {
            $Config->overload(Config::loadBlogConfig(BID));
        }
        $_config = null;

        if (!!$rid && !$mid) {
            $_config = Config::loadRuleConfig($rid, $setid);
        } elseif (!!$mid) {
            $_config = Config::loadModuleConfig($mid, $rid);
        }

        if (!!$_config) {
            $Config->overload($_config);
        }

        // typeで参照できるラベルの連想配列
        $aryTypeLabel    = array();
        foreach ($Config->getArray('column_add_type') as $i => $_type) {
            $aryTypeLabel[$_type]    = $Config->get('column_add_type_label', '', $i);
        }

        // 特定指定子を含むユニットタイプ
        $actualType = $type;
        // 特定指定子を除外した、一般名のユニット種別
        $type = detectUnitTypeSpecifier($type);

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        $Column = new Field();
        $Column->setField('pfx', $pfx);


        switch ($type) {
            case 'text':
                foreach ($Config->getArray('column_text_tag') as $i => $tag) {
                    $Tpl->add(array('textTag:loop', $type), array(
                        'value' => $tag,
                        'label' => $Config->get('column_text_tag_label', '', $i),
                    ));
                }
                break;
            case 'table':
                break;
            case 'image':
                foreach ($Config->getArray('column_image_size') as $j => $size) {
                    $Tpl->add(array('size:loop', $type), array(
                        'value' => $size,
                        'label' => $Config->get('column_image_size_label', '', $j),
                    ));
                }
                break;
            case 'file':
                break;
            case 'osmap':
            case 'map':
                foreach ($Config->getArray('column_map_size') as $j => $size) {
                    $Tpl->add(array('size:loop', $type), array(
                        'value' => $size,
                        'label' => $Config->get('column_map_size_label', '', $j),
                    ));
                }
                break;
            case 'youtube':
                foreach ($Config->getArray('column_youtube_size') as $j => $size) {
                    $Tpl->add(array('size:loop', $type), array(
                        'value' => $size,
                        'label' => $Config->get('column_youtube_size_label', '', $j),
                    ));
                }
                break;
            case 'video':
                foreach ($Config->getArray('column_video_size') as $j => $size) {
                    $Tpl->add(array('size:loop', $type), array(
                        'value' => $size,
                        'label' => $Config->get('column_video_size_label', '', $j),
                    ));
                }
                break;
            case 'eximage':
                foreach ($Config->getArray('column_eximage_size') as $j => $size) {
                    $Tpl->add(array('size:loop', $type), array(
                        'value' => $size,
                        'label' => $Config->get('column_eximage_size_label', '', $j),
                    ));
                }
                break;
            case 'quote':
                break;
            case 'media':
                foreach ($Config->getArray('column_media_size') as $j => $size) {
                    $Tpl->add(array('size:loop', $type), array(
                        'value' => $size,
                        'label' => $Config->get('column_media_size_label', '', $j),
                    ));
                }
                break;
            case 'rich-editor':
                break;
            case 'module':
                break;
            case 'break':
                break;
            case 'custom':
                break;
            default:
                return '';
        }

        if (
            1
            && 'on' === $Config->get('unit_group')
            && !preg_match('/^(break|module|custom)$/', $type)
        ) {
            $classes = $Config->getArray('unit_group_class');
            $labels  = $Config->getArray('unit_group_label');
            foreach ($labels as $i => $label) {
                $Tpl->add(array('group:loop', 'group:veil', $type), array(
                     'group.value'     => $classes[$i],
                     'group.label'     => $label,
                     'group.selected'  => ($classes[$i] === $Config->get('group')) ? $Config->get('attr_selected') : '',
                ));
            }
            $Tpl->add(array('group:veil', $type), array(
                'group.pfx' => $Column->get('pfx'),
            ));
        }

        $vars   = $this->buildField($Column, $Tpl, $type, 'column');
        $vars  += array(
            'actualType'  => $actualType,
            'actualLabel' => $aryTypeLabel[$actualType],
        );

        $Tpl->add($type, $vars);
        return $Tpl->get();
    }
}
