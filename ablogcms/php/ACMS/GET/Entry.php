<?php

use Acms\Services\Facades\RichEditor;

class ACMS_GET_Entry extends ACMS_GET
{
    protected function buildColumn(&$Column, &$Tpl, $eid, $preAlign = null, $renderGroup = true)
    {
        $entry = ACMS_RAM::entry($eid);
        $rootBlock = array('unit:loop');
        $columnAmount = count($Column) - 1;
        $currentGroup = null;
        $squareImgSize = config('image_size_square');
        $showInvisible = (1
            && sessionWithContribution(BID)
            && roleEntryUpdateAuthorization(BID, $entry)
            && 'on' === config('entry_edit_inplace_enable')
            && 'on' === config('entry_edit_inplace')
            && (!enableApproval() || sessionWithApprovalAdministrator())
            && $entry['entry_approval'] !== 'pre_approval'
            && VIEW === 'entry'
        );
        $unitGroupEnable= (config('unit_group') === 'on');
        $mediaDataList = Media::mediaEagerLoadFromUnit($Column);

        foreach ($Column as $k => $data) {
            $type = $data['type'];
            $align = $data['align'];
            $sort = $data['sort'];
            $group = $data['group'];
            $utid = $data['clid'];
            $count = 0;

            // 特定指定子を含むユニットタイプ
            $actualType = $type;
            // 特定指定子を除外した、一般名のユニット種別
            $type = detectUnitTypeSpecifier($type);


            if (!$showInvisible && 'hidden' === $align) {
                continue;
            }
            //-------
            // group
            if (1
                && $unitGroupEnable
                && $group !== ''
                && $renderGroup === true
            ) {
                $class = $group;

                // close rear
                if (!!$currentGroup) {
                    $Tpl->add(array('unitGroup#rear', 'unit:loop'));
                }

                // open front
                $grVars = array('class' => $class);
                if ($currentGroup === $class) {
                    $count += 1;
                    $grVars['i'] = $count;
                } else {
                    $count = 1;
                    $grVars['i'] = $count;
                }

                if ($class === config('unit_group_clear', 'acms-column-clear')) {
                    $currentGroup = null;
                } else {
                    $Tpl->add(array_merge(array('unitGroup#front'), $rootBlock), $grVars);
                    $currentGroup = $class;
                }
            }

            //-------
            // clear
            if ($type !== 'break') {
                do {
                    if (empty($preAlign)) {
                        break;
                    };
                    if ($align === 'left' && $preAlign === 'left') {
                        break;
                    };
                    if ($align === 'right' && $preAlign === 'right') {
                        break;
                    }
                    if ($align === 'auto') {
                        if ($preAlign === 'left') {
                            break;
                        }
                        if ($preAlign === 'right') {
                            break;
                        }
                        if ($preAlign === 'auto' && $type === 'text') {
                            break;
                        }
                    }
                    $Tpl->add(array_merge(array('clear'), $rootBlock));
                } while (false);

                if ($align === 'auto' && '$type' !== 'text') {
                    $data['align'] = !empty($preAlign) ? $preAlign : 'auto';
                }
                $preAlign  = $align;
            }

            //------
            // text
            if ($type === 'text') {
                if (empty($data['text'])) {
                    continue;
                }

                $vars = array(
                    'text' => $data['text'],
                    'extend_tag' => $data['extend_tag'],
                );
                buildUnitData($vars['text'], $vars, 'text');

                if (!empty($data['attr'])) {
                    $vars['attr'] = $data['attr'];
                    $vars['class'] = $data['attr']; // legacy
                }

                $vars['utid'] = $utid;
                $vars['unit_eid'] = $eid;
                $vars['extend_tag'] = $data['extend_tag'];
                $Tpl->add(array_merge(array($data['tag'], 'unit#' . $actualType), $rootBlock), $vars);
                $Tpl->add(array_merge(array('unit#' . $actualType), $rootBlock), array(
                    'align' => $data['align'],
                ));

                //-------
                // table
            } elseif ($type === 'table') {
                if (empty($data['table'])) {
                    continue;
                }
                $vars = array(
                    'table' => $data['table'],
                );
                if (!empty($data['attr'])) {
                    $vars['attr'] = $data['attr'];
                }
                buildUnitData($vars['table'], $vars, 'table');
                $vars['utid'] = $utid;
                $vars['unit_eid'] = $eid;
                $Tpl->add(array_merge(array('unit#' . $actualType), $rootBlock), $vars);

            //-------
            // image
            } elseif ($type === 'image') {
                if (empty($data['path'])) {
                    continue;
                }

                $vars = array();
                $pathAry = explodeUnitData($data['path']);

                foreach ($pathAry as $i => $path_) {
                    if (empty($i)) {
                        $i = '';
                    } else {
                        $i++;
                    }
                    $path = ARCHIVES_DIR.$path_;
                    $xy = Storage::getImageSize($path);

                    $vars['path'.$i] = $path;
                    $vars['x'.$i] = isset($xy[0]) ? $xy[0] : '';
                    $vars['y'.$i] = isset($xy[1]) ? $xy[1] : '';
                }

                $vars['alt'] = $data['alt'];

                if (isset($data['exif'])) {
                    $vars['exif'] = $data['exif'];
                }

                if (!empty($data['display_size'])) {
                    $dsize = $data['display_size'];
                    if (is_numeric($dsize) && intval($dsize) > 0) {
                        $vars['display_size']   = ' style="width: ' . $data['display_size'] . '%"';
                    } else {
                        $viewClass = $data['display_size'];
                        $viewClass = ltrim($viewClass, '.');
                        $vars['display_size_class'] = ' js_notStyle ' . $viewClass;
                    }
                }

                if (!empty($data['caption'])) {
                    $vars['caption'] = $data['caption'];
                }

                $vars['align']  = $data['align'];
                if (!empty($data['attr'])) {
                    $vars['attr'] = $data['attr'];
                }

                $linkAry = explodeUnitData($data['link']);
                foreach ($pathAry as $i => $path_) {
                    $j = empty($i) ? '' : $i + 1;

                    $link_ = isset($linkAry[$i]) ? $linkAry[$i] : '';
                    if (empty($link_)) {
                        if (isset($pathAry[$i])) {
                            $path = ARCHIVES_DIR . $pathAry[$i];
                        } else {
                            $path = ARCHIVES_DIR . $data['path'];
                        }
                        $name = Storage::mbBasename($path);
                        $large = substr($path, 0, strlen($path) - strlen($name)) . 'large-' . $name;
                        if ($xy = Storage::getImageSize($large)) {
                            $Tpl->add(
                                array_merge(array('link'. $j .'#front', 'unit#' . $actualType), $rootBlock),
                                array(
                                    'url' . $j    => BASE_URL . $large,
                                    'viewer' . $j => str_replace('{unit_eid}', $eid, config('entry_body_image_viewer')),
                                    'caption' . $j => $data['caption'],
                                    'link_eid' . $j => $eid
                                )
                            );
                            $Tpl->add(array_merge(array('link' . $j . '#rear', 'unit#' . $actualType), $rootBlock));
                        }
                    } else {
                        $Tpl->add(array_merge(array('link'.$j.'#front', 'unit#'.$actualType), $rootBlock), array(
                            'url' . $j  => $link_,
                        ));
                        $Tpl->add(array_merge(array('link'.$j.'#rear', 'unit#'.$actualType), $rootBlock));
                    }
                }

                $tiny = otherSizeImagePath($path, 'tiny');
                if ($xy = Storage::getImageSize($tiny)) {
                    $vars['tinyPath'] = $tiny;
                    $vars['tinyX'] = $xy[0];
                    $vars['tinyY'] = $xy[1];
                }

                $square = otherSizeImagePath($path, 'square');
                if (Storage::isFile($square)) {
                    $vars['squarePath'] = $square;
                    $vars['squareX'] = $squareImgSize;
                    $vars['squareY'] = $squareImgSize;
                }

                $large = otherSizeImagePath($path, 'large');
                if ($xy = Storage::getImageSize($large)) {
                    $vars['largePath'] = $large;
                    $vars['largeX'] = $xy[0];
                    $vars['largeY'] = $xy[1];
                }

                $vars['utid'] = $utid;
                $vars['unit_eid'] = $eid;

                foreach ($vars as $key => $val) {
                     buildUnitData($val, $vars, $key);
                }
                $Tpl->add(array_merge(array('unit#' . $actualType), $rootBlock), $vars);

            //------
            // file
            } elseif ($type === 'file') {
                if (empty($data['path'])) {
                    continue;
                }
                $pathAry = explodeUnitData($data['path']);
                $vars = array();

                foreach ($pathAry as $i => $val) {
                    $fx = empty($i) ? '' : $i + 1;

                    $path = ARCHIVES_DIR . $val;
                    $ext = ite(pathinfo($path), 'extension');
                    $icon = pathIcon($ext);
                    if (!Storage::exists($icon)) {
                        continue;
                    }
                    $vars += array(
                        'path'.$fx => $path,
                        'icon'.$fx => $icon,
                        'x'.$fx => 70,
                        'y'.$fx => 81,
                    );
                    if (config('file_icon_size') === 'dynamic') {
                        $xy = Storage::getImageSize($icon);
                        $vars['x' . $fx] = isset($xy[0]) ? $xy[0] : 70;
                        $vars['y' . $fx] = isset($xy[1]) ? $xy[1] : 81;
                    }
                }

                if (!empty($data['caption'])) {
                    $vars['caption'] = $data['caption'];
                }

                buildUnitData($data['caption'], $vars, 'caption');

                $vars['align']  = $data['align'];

                if (!empty($data['attr'])) {
                    $vars['attr'] = $data['attr'];
                }

                $vars['utid'] = $utid;
                $vars['unit_eid'] = $eid;
                $Tpl->add(array_merge(array('unit#'.$actualType), $rootBlock), $vars);

            //-----
            // map
            } elseif ($type === 'map') {
                if (empty($data['lat'])) {
                    continue;
                }
                list($x, $y) = explode('x', $data['size']);
                $msg = str_replace(array(
                    '"', '<', '>', '&'
                ), array(
                    '[[:quot:]]', '[[:lt:]]', '[[:gt:]]', '[[:amp:]]'
                ), $data['msg']);
                $vars = array(
                    'lat'   => $data['lat'],
                    'lng'   => $data['lng'],
                    'zoom'  => $data['zoom'],
                    'msg'   => $msg,
                    'msgRaw'    => $data['msg'],
                    'x'     => $x,
                    'y'     => $y,
                    'align' => $data['align'],
                    'view_pitch' => $data['view_pitch'],
                    'view_zoom' => $data['view_zoom'],
                    'view_heading' => $data['view_heading'],
                    'view_activate' => $data['view_activate']
                );
                if (!empty($data['display_size'])) {
                    $dsize = $data['display_size'];
                    if (is_numeric($dsize) && intval($dsize) > 0) {
                        $vars['display_size'] = ' style="width: ' . $data['display_size'] . '%"';
                    } else {
                        $viewClass = $data['display_size'];
                        $viewClass = ltrim($viewClass, '.');
                        $vars['display_size_class'] = ' js_notStyle ' . $viewClass;
                    }
                }
                if (!empty($data['attr'])) {
                    $vars['attr'] = $data['attr'];
                }
                $vars['utid'] = $utid;
                $vars['unit_eid'] = $eid;
                $Tpl->add(array_merge(array('unit#'.$actualType), $rootBlock), $vars);
            } elseif ($type === 'osmap') {
                if (empty($data['lat'])) {
                    continue;
                }

                list($x, $y) = explode('x', $data['size']);
                $msg = str_replace(array(
                    '"', '<', '>', '&'
                ), array(
                    '[[:quot:]]', '[[:lt:]]', '[[:gt:]]', '[[:amp:]]'
                ), $data['msg']);
                $vars = array(
                    'lat'   => $data['lat'],
                    'lng'   => $data['lng'],
                    'zoom'  => $data['zoom'],
                    'msg'   => $msg,
                    'msgRaw'    => $data['msg'],
                    'x'     => $x,
                    'y'     => $y,
                    'align' => $data['align'],
                );
                if (!empty($data['display_size'])) {
                    $dsize = $data['display_size'];
                    if (is_numeric($dsize) && intval($dsize) > 0) {
                        $vars['display_size']   = ' style="width: ' . $data['display_size'] . '%"';
                    } else {
                        $viewClass = $data['display_size'];
                        $viewClass = ltrim($viewClass, '.');
                        $vars['display_size_class'] = ' js_notStyle ' . $viewClass;
                    }
                }
                if (!empty($data['attr'])) {
                    $vars['attr'] = $data['attr'];
                }
                $vars['utid'] = $utid;
                $vars['unit_eid'] = $eid;
                $Tpl->add(array_merge(array('unit#'.$actualType), $rootBlock), $vars);
            //---------
            // youtube
            } elseif ($type === 'youtube') {
                if (empty($data['youtube_id'])) {
                    continue;
                }

                list($x, $y) = explode('x', $data['size']);
                $vars = array(
                    'youtubeId' => $data['youtube_id'],
                    'x' => $x,
                    'y' => $y,
                    'align' => $data['align'],
                );
                buildUnitData($data['youtube_id'], $vars, 'youtubeId');

                if (!empty($data['display_size'])) {
                    $dsize = $data['display_size'];
                    if (is_numeric($dsize) && intval($dsize) > 0) {
                        $vars['display_size'] = ' style="width: ' . $data['display_size'] . '%"';
                    } else {
                        $viewClass = $data['display_size'];
                        $viewClass = ltrim($viewClass, '.');
                        $vars['display_size_class'] = ' js_notStyle ' . $viewClass;
                    }
                }

                if (!empty($data['attr'])) {
                    $vars['attr'] = $data['attr'];
                }

                $vars['utid']       = $utid;
                $vars['unit_eid']   = $eid;
                $Tpl->add(array_merge(array('unit#'.$actualType), $rootBlock), $vars);

            //---------
            // video
            } elseif ($type === 'video') {
                if (empty($data['video_id'])) {
                    continue;
                }

                $x = '';
                $y = '';
                $sizeAry = explode('x', $data['size']);
                if (count($sizeAry) > 1) {
                    list($x, $y) = $sizeAry;
                }
                $vars = array(
                    'videoId' => $data['video_id'],
                    'x' => $x,
                    'y' => $y,
                    'align' => $data['align'],
                );
                buildUnitData($data['video_id'], $vars, 'videoId');

                if (!empty($data['display_size'])) {
                    $dsize = $data['display_size'];
                    if (is_numeric($dsize) && intval($dsize) > 0) {
                        $vars['display_size'] = ' style="width: ' . $data['display_size'] . '%"';
                    } else {
                        $viewClass = $data['display_size'];
                        $viewClass = ltrim($viewClass, '.');
                        $vars['display_size_class'] = ' js_notStyle ' . $viewClass;
                    }
                }

                if (!empty($data['attr'])) {
                    $vars['attr'] = $data['attr'];
                }

                $vars['utid'] = $utid;
                $vars['unit_eid'] = $eid;
                $Tpl->add(array_merge(array('unit#'.$actualType), $rootBlock), $vars);

            //---------
            // eximage
            } elseif ($type === 'eximage') {
                if (empty($data['normal'])) {
                    continue;
                }
                $x = '';
                $y = '';
                $sizeAry = explode('x', $data['size']);
                if (count($sizeAry) > 1) {
                    list($x, $y) = $sizeAry;
                }
                $normalAry = explodeUnitData($data['normal']);
                $linkAry = explodeUnitData($data['link']);
                $largeAry = explodeUnitData($data['large']);
                foreach ( $normalAry as $i => $normal ) {
                    $j = empty($i) ? '' : $i + 1;

                    $link_  = isset($linkAry[$i]) ? $linkAry[$i] : '';
                    $large_ = isset($largeAry[$i]) ? $largeAry[$i] : '';

                    $url = !empty($link_) ? $link_ : (!empty($large_) ? $large_ : null);
                    if (!empty($url)) {
                        $vars = array(
                            'url' . $j => $url,
                            'link_eid' . $j => $eid
                        );

                        if (empty($link_)) {
                            $vars['viewer' . $j] = str_replace('{unit_eid}', $eid, config('entry_body_image_viewer'));
                        }

                        $Tpl->add(array_merge(array('link'.$j.'#front', 'unit#'.$actualType), $rootBlock), $vars);
                        $Tpl->add(array_merge(array('link'.$j.'#rear', 'unit#'.$actualType), $rootBlock));
                    }
                }

                $vars = array(
                    'normal'    => $data['normal'],
                    'x'         => $x,
                    'y'         => $y,
                    'alt'       => $data['alt'],
                    'large'     => $data['large'],
                    'caption'   => '',
                );

                if (!empty($data['display_size'])) {
                    $dsize = $data['display_size'];
                    if (is_numeric($dsize) && intval($dsize) > 0) {
                        $vars['display_size'] = ' style="width: ' . $data['display_size'] . '%"';
                    } else {
                        $viewClass = $data['display_size'];
                        $viewClass = ltrim($viewClass, '.');
                        $vars['display_size_class'] = ' js_notStyle '.$viewClass;
                    }
                }

                if (!empty($data['caption'])) {
                    $vars['caption'] = $data['caption'];
                }

                $vars['align'] = $data['align'];
                $vars['utid'] = $utid;
                $vars['unit_eid'] = $eid;
                if (!empty($data['attr'])) {
                    $vars['attr'] = $data['attr'];
                }

                buildUnitData($vars['normal'], $vars, 'normal');
                buildUnitData($x, $vars, 'x');
                buildUnitData($y, $vars, 'y');
                buildUnitData($vars['alt'], $vars, 'alt');
                buildUnitData($vars['large'], $vars, 'large');
                buildUnitData($vars['caption'], $vars, 'caption');

                $Tpl->add(array_merge(array('unit#'.$actualType), $rootBlock), $vars);

            //-------
            // quote
            } elseif ($type === 'quote') {
                if (empty($data['quote_url'])) {
                    continue;
                }

                $url = $data['quote_url'];
                $vars = array(
                    'quote_url' => $url,
                );
                buildUnitData($vars['quote_url'], $vars, 'quote_url');

                if (!empty($data['html'])) {
                    $vars['quote_html'] = $data['html'];
                    buildUnitData($vars['quote_html'], $vars, 'quote_html');
                }
                if (!empty($data['site_name'])) {
                    $vars['quote_site_name'] = $data['site_name'];
                    buildUnitData($vars['quote_site_name'], $vars, 'quote_site_name');
                }
                if (!empty($data['author'])) {
                    $vars['quote_author'] = $data['author'];
                    buildUnitData($vars['quote_author'], $vars, 'quote_author');
                }
                if (!empty($data['title'])) {
                    $vars['quote_title'] = $data['title'];
                    buildUnitData($vars['quote_title'], $vars, 'quote_title');
                }
                if (!empty($data['description'])) {
                    $vars['quote_description'] = $data['description'];
                    buildUnitData($vars['quote_description'], $vars, 'quote_description');
                }
                if (!empty($data['image'])) {
                    $vars['quote_image'] = $data['image'];
                    buildUnitData($vars['quote_image'], $vars, 'quote_image');
                }

                $vars['align'] = $data['align'];
                $vars['utid'] = $utid;
                $vars['unit_eid'] = $eid;
                if (!empty($data['attr'])) {
                    $vars['attr'] = $data['attr'];
                }

                $Tpl->add(array('unit#'.$actualType), $vars);

            //-------
            // media
            } elseif ($type === 'media') {
                if (empty($data['media_id'])) {
                    continue;
                }
                $midAry = explodeUnitData($data['media_id']);
                $mediaCaptions = explodeUnitData($data['caption']);
                $mediaAlts = explodeUnitData($data['alt']);
                $mediaSizes = explodeUnitData($data['size']);
                $mediaAlign = $data['align'];
                $mediaAttr = $data['attr'];
                $mediaLarges = explodeUnitData($data['enlarged']);
                $mediaUseIcons = explodeUnitData($data['use_icon']);
                $mediaLinks = explodeUnitData($data['link']);
                $displaySize = $data['display_size'];

                foreach ($midAry as $i => $mid) {
                    $fx = empty($i) ? '' : $i + 1;

                    $mid = intval($mid);
                    $vars = array();

                    if (!isset($mediaDataList[$mid])) {
                        continue;
                    }

                    $media = $mediaDataList[$mid];
                    $path = Media::urlencode($media['media_path']);
                    $type = $media['media_type'];

                    $vars['caption' . $fx] = (isset($mediaCaptions[$i]) && $mediaCaptions[$i])
                                            ? $mediaCaptions[$i]
                                            : $media['media_field_1'];

                    $vars['alt' . $fx] = (isset($mediaAlts[$i]) && $mediaAlts[$i])
                                        ? $mediaAlts[$i]
                                        : $media['media_field_3'];

                    if (!empty($media['media_field_4'])) {
                        $vars['text' . $fx] = $media['media_field_4'];
                    }

                    if (Media::isImageFile($type) || Media::isSvgFile($type)) {
                        $vars['path' . $fx] = MEDIA_LIBRARY_DIR . $path;
                        $size = isset($mediaSizes[$i]) ? $mediaSizes[$i] : '';
                        $unitLink = isset($mediaLinks[$i]) ? $mediaLinks[$i] : '';
                        $link = $unitLink ? $unitLink : $media['media_field_2'];
                        $url = false;
                        $originalX = 999999999;
                        $originalY = 999999999;
                        if (strpos($media['media_image_size'], 'x') !== false) {
                            list($tempX, $tempY) = explode('x', $media['media_image_size']);
                            $originalX = intval(trim($tempX));
                            $originalY = intval(trim($tempY));
                        }
                        if (strpos($size, 'x') !== false) {
                            list($tempX, $tempY) = explode('x', $size);
                            if ($originalX >= $tempX && $originalY >= $tempY) {
                                $vars['x' . $fx] = $tempX;
                                $vars['y' . $fx] = $tempY;
                            } else {
                                $vars['x' . $fx] = $originalX;
                                $vars['y' . $fx] = $originalY;
                            }
                        } elseif ($originalX > 0 && $originalY > 0) {
                            $tempX = isset($mediaSizes[$i]) ? $mediaSizes[$i] : '';
                            $tempY = intval(intval($tempX) * ($originalY / $originalX));
                            if (!empty($tempX) && !empty($tempY) && $originalX >= $tempX && $originalY >= $tempY) {
                                $vars['x' . $fx] = $tempX;
                                $vars['y' . $fx] = $tempY;
                            } else {
                                $vars['x' . $fx] = $originalX;
                                $vars['y' . $fx] = $originalY;
                            }
                        } elseif (Media::isSvgFile($type)) {
                            $vars['x' . $fx] = isset($mediaSizes[$i]) ? $mediaSizes[$i] : '';
                            $vars['y' . $fx] = $vars['x' . $fx];

                            $doc = new DOMDocument();
                            if ($doc->loadXML(file_get_contents(MEDIA_LIBRARY_DIR . $path))) {
                                $svg = $doc->getElementsByTagName('svg');
                                $item = $svg->item(0);
                                $svgWidth = intval($item->getAttribute('width'));
                                $svgHeight = intval($item->getAttribute('height'));
                                if (empty($svgWidth) || empty($svgHeight)) {
                                    if ($viewBox = $item->getAttribute('viewBox')) {
                                        $viewBox = explode(' ', $viewBox);
                                        $svgWidth = intval($viewBox[2]);
                                        $svgHeight = intval($viewBox[3]);
                                    }
                                }
                                if ($svgWidth > 0 && $svgHeight > 0) {
                                    $vars['y' . $fx] = intval(intval($vars['x' . $fx]) * ($svgHeight / $svgWidth));
                                }
                            }
                        } else {
                            $vars['x' . $fx] = isset($mediaSizes[$i]) ? $mediaSizes[$i] : '';
                            $vars['y' . $fx] = '';
                        }
                        if ($link) {
                            $url = setGlobalVars($link);
                        } elseif (isset($mediaLarges[$i]) && $mediaLarges[$i] !== 'no') {
                            $url = Media::getImagePermalink($path);
                        }
                        if (!empty($url) && isset($mediaLarges[$i]) && $mediaLarges[$i] !== 'no') {
                            $varsLink = array(
                                'url' . $fx => $url,
                                'link_eid' . $fx => $eid,
                            );
                            if (!$link) {
                                $varsLink['viewer' . $fx] = str_replace(
                                    '{unit_eid}',
                                    $eid,
                                    config('entry_body_image_viewer')
                                );
                            }
                            $Tpl->add(
                                array_merge(
                                    array(
                                        'link' . $fx . '#front',
                                        'type' . $fx . '#' . $media['media_type'],
                                        'unit#' . $actualType
                                    ),
                                    $rootBlock
                                ),
                                $varsLink
                            );
                            $Tpl->add(
                                array_merge(
                                    array(
                                        'link' . $fx . '#rear',
                                        'type' . $fx . '#' . $media['media_type'],
                                        'unit#' . $actualType
                                    ),
                                    $rootBlock
                                )
                            );
                        }
                    } elseif (Media::isFile($type)) {
                        if (empty($media['media_status'])) {
                            $url = Media::getFileOldPermalink($path, false);
                        } else {
                            $url = Media::getFilePermalink($mid, false);
                        }
                        $icon = pathIcon($media['media_extension']);
                        $vars += array(
                            'url' . $fx => $url,
                            'icon'.$fx => $icon,
                            'x'.$fx => 70,
                            'y'.$fx => 81,
                        );
                        if (config('file_icon_size') === 'dynamic') {
                            $xy = Storage::getImageSize($icon);
                            $vars['x' . $fx] = isset($xy[0]) ? $xy[0] : 70;
                            $vars['y' . $fx] = isset($xy[1]) ? $xy[1] : 81;
                        }
                        if (!empty($media['media_thumbnail'])) {
                            $vars['thumbnail' . $fx] = $media['media_thumbnail'];
                            if (isset($mediaUseIcons[$i])) {
                                $vars['use_icon'.$fx] = $mediaUseIcons[$i];
                            }
                        }
                    }
                    $vars['attr'] = $mediaAttr;
                    $Tpl->add(
                        array_merge(
                            array(
                                'type' . $fx . '#' . $media['media_type'],
                                'unit#' . $actualType
                            ),
                            $rootBlock
                        ),
                        $vars
                    );
                }


                $varsRoot = array();
                if ($displaySize) {
                    $dsize = $displaySize;
                    if (is_numeric($dsize) && intval($dsize) > 0) {
                        $varsRoot['display_size'] = ' style="width: ' . $displaySize . '%"';
                    } else {
                        $viewClass = $displaySize;
                        $viewClass = ltrim($viewClass, '.');
                        $varsRoot['display_size_class'] = ' js_notStyle ' . $viewClass;
                    }
                }

                $varsRoot['align'] = $mediaAlign;
                $varsRoot['attr'] = $mediaAttr;
                $varsRoot['utid'] = $utid;
                $varsRoot['unit_eid'] = $eid;

                $Tpl->add(array_merge(array('unit#'.$actualType), $rootBlock), $varsRoot);

            } elseif ($type === 'rich-editor') {
                if (empty($data['json'])) {
                    continue;
                }

                $vars = array(
                    'html' => RichEditor::render($data['json']),
                );
                buildUnitData($vars['html'], $vars, 'html');
                $vars['utid'] = $utid;
                $vars['unit_eid'] = $eid;
                $Tpl->add(array_merge(array('unit#' . $actualType), $rootBlock), $vars);
            //-------
            // break
            } elseif ($type === 'break') {

                if (empty($data['label'])) {
                    continue;
                }

                $vars = array();
                buildUnitData($data['label'], $vars, 'label');

                if (!empty($data['attr'])) {
                    $vars['attr']   = $data['attr'];
                    $vars['class']  = $data['attr']; // legacy
                }

                $vars['utid'] = $utid;
                $vars['unit_eid'] = $eid;
                $vars['align'] = $data['align'];

                $Tpl->add(array_merge(array('unit#'.$actualType), $rootBlock), $vars);

            //--------
            // module
            } elseif ($type === 'module') {

                if (empty($data['mid'])) {
                    continue;
                }

                // ToDo: モジュールのテンプレートを構築
                $mid = $data['mid'];
                $tpl = $data['tpl'];
                if (!empty($mid)) {
                    $module     = loadModule($mid);
                    $name       = $module->get('name');
                    $identifier = $module->get('identifier');
                    $vars['view'] = Tpl::spreadModule($name, $identifier, $tpl);
                }

                if (!empty($data['attr'])) {
                    $vars['attr'] = $data['attr'];
                    $vars['class'] = $data['attr']; // legacy
                }

                $vars['utid'] = $utid;
                $vars['unit_eid'] = $eid;
                $vars['align'] = $data['align'];

                $Tpl->add(array_merge(array('unit#'.$actualType), $rootBlock), $vars);

            //--------
            // custom
            } elseif ($type === 'custom') {
                if (empty($data['field'])) {
                    continue;
                }

                $vars  = array();
                if (!empty($data['attr'])) {
                    $vars['attr']   = $data['attr'];
                    $vars['class']  = $data['attr']; // legacy
                }

                $vars['utid'] = $utid;
                $vars['unit_eid'] = $eid;
                $vars['align'] = $data['align'];

                $Field = acmsUnserialize($data['field']);
                Tpl::injectMediaField($Field, true);
                foreach ($Field->listFields() as $fd) {
                    if ( 1
                        && !strpos($fd, '@path')
                        && !strpos($fd, '@tinyPath')
                        && !strpos($fd, '@largePath')
                        && !strpos($fd, '@squarePath')
                    ) {
                        continue;
                    }
                    $set = false;
                    foreach ($Field->getArray($fd, true) as $i => $path) {
                        if (!$set) {
                            $Field->delete($fd);
                            $set = true;
                        }
                        $Field->add($fd, $path);
                    }
                }

                $block = array_merge(array('unit#'.$actualType), $rootBlock);
                $vars += $this->buildField($Field, $Tpl, $block, null, array(
                    'utid' => $utid,
                ));
                $Tpl->add($block, $vars);
            } else {
                continue;
            }

            //--------------
            // edit inplace
            if (1
                && VIEW === 'entry'
                && 'on' === config('entry_edit_inplace_enable')
                && 'on' === config('entry_edit_inplace')
                && (!enableApproval() || sessionWithApprovalAdministrator() )
                && $entry['entry_approval'] !== 'pre_approval'
                && !ADMIN
                && (0
                    || roleEntryUpdateAuthorization(BID, $entry)
                    || (1
                        && sessionWithContribution()
                        && SUID == ACMS_RAM::entryUser($eid)
                    )
                )
            ) {
                $vars = array();
                $vars['unit:loop.type'] = $actualType;
                $vars['unit:loop.utid'] = $utid;
                $vars['unit:loop.unit_eid'] = $eid;
                $vars['unit:loop.sort'] = $sort;
                $vars['unit:loop.align'] = $align;
                $Tpl->add(array_merge(array('inplace#front'), $rootBlock), $vars);
                $Tpl->add(array_merge(array('inplace#rear'), $rootBlock));
            }

            //-------------
            // close group
            if ($k === $columnAmount && $currentGroup !== null) {
                $Tpl->add(array_merge(array('unitGroup#last'), $rootBlock));
            }

            $Tpl->add($rootBlock);
        }

        // ユニットグループでかつ最後の要素が非表示だった場合
        $lastUnit = array_pop($Column);
        if (!$showInvisible && isset($lastUnit['align']) && $lastUnit['align'] === 'hidden' && $currentGroup !== null) {
            $Tpl->add(array_merge(array('unitGroup#last'), $rootBlock));
            $Tpl->add($rootBlock);
        }
        return true;
    }
}
