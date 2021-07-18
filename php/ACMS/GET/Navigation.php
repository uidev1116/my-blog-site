<?php

class ACMS_GET_Navigation extends ACMS_GET
{
    var $parentNavi = array();

    function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $this->buildModuleField($Tpl);

        if ( !$labels = configArray('navigation_label') ) return '';

        $Parent     = array();
        $notPublish = array();
        $levelLabel = configArray('navigation_ul_level');

        foreach ( $labels as $i => $label ) {
            $id     = $i + 1;

            $pid    = intval(config('navigation_parent', 0, $i));
            if ( config('navigation_publish', null, $i) === 'on' ) {
                $Parent[$pid][$id]  = array(
                    'id'        => $id,
                    'pid'       => $pid,
                    'label'     => $label,
                    'uri'       => config('navigation_uri', null, $i),
                    'target'    => config('navigation_target', null, $i),
                    'attr'      => config('navigation_attr', null, $i),
                    'a_attr'      => config('navigation_a_attr', null, $i),
                    'end'       => array(),
                );
            } else {
                $notPublic[] = $id;
            }
            $this->parentNavi[$id] = $pid;
        }

        if ( count($Parent) === 0) {
            return $Tpl->get();
        }

        foreach ( $notPublish as $nid ) {
            foreach ( $Parent[$nid] as & $obj ) {
                unset($obj);
            }
        }

        $all        = array();
        $pidStack   = array(0);
        while ( count($pidStack) ) {
            $pid    = array_pop($pidStack);
            while ( $row = array_shift($Parent[$pid]) ) {
                $id = $row['id'];
                $row['end'][]   = 'li#rear';
                $all[] = $row;
                if ( isset($Parent[$id]) ) {
                    $pidStack[] = $pid;
                    $pidStack[] = $id;
                    break;
                }
            }
            if ( !empty($row) ) {
                $row    = array_pop($all);
                $row['end']   = array('ul#front');
                $all[] = $row;
            } else if ( !empty($pidStack) ) {
                $row    = array_pop($all);
                $row['end'][]   = 'ul#rear';
                $row['end'][]   = 'li#rear';
                $all[] = $row;
            }
        }

        $lvLabel    = isset($levelLabel[0]) ? $levelLabel[0] : '1';
        $Tpl->add('ul#front', array('ulLevel' => $lvLabel));
        foreach ( $all as $row ) {
            $uri        = $row['uri'];
            $label      = $row['label'];

            if ( !preg_match('/^#$/', $uri) ) {
                $acmsPath   = preg_replace('@^acms://@', '', $uri);
                if ( $uri <> $acmsPath ) {
                    $Q      = parseAcmsPath($acmsPath);
                    $rep    = array();

                    if ( !$Q->isNull('bid') ) {
                        $rep['%{BLOG_NAME}']    = ACMS_RAM::blogName($Q->get('bid'));
                    }
                    if ( !$Q->isNull('cid') ) {
                        $rep['%{CATEGORY_NAME}']    = ACMS_RAM::categoryName($Q->get('cid'));
                    }
                    if ( !$Q->isNull('eid') ) {
                        $rep['%{ENTRY_TITLE}']  = ACMS_RAM::entryTitle($Q->get('eid'));
                    }

                    $label  = str_replace(array_keys($rep), array_values($rep), $label);

                    $uri    = acmsLink($Q, false);
                } else {
                    //$uri    = setGlobalVars($uri);
                    $label  = setGlobalVars($label);
                }
                $_target    = $row['target'];
                $lvBlock    = 'level_'.strval($this->buildLevel(intval($row['id'])));
                $Tpl->add(array($lvBlock, 'link#front', 'navigation:loop'));
                if ( in_array('ul#front', $row['end']) ) {
                    $Tpl->add(array('childNavi', 'link#front', 'navigation:loop'));
                }
                $Tpl->add(array('link#front', 'navigation:loop'), array(
                    'url'       => $uri,
                    'target'    => $_target,
                    'attr'  => (substr($row['a_attr'], 0, 1) !== ' ' ? ' ' : '').$row['a_attr'],
                ));
                $Tpl->add(array('link#rear', 'navigation:loop'));
            }

            if ( preg_match('@^(https|http|acms)://@', $label, $match) ) {
                if ( !preg_match('@^ablogcms@', UA) ) { // against double load
                    $location   = null;
                    if ( 'acms' == $match[1] ) {
                        $Q  = parseAcmsPath(preg_replace('@^acms://@', '', $label));
                        $location   = acmsLink($Q, false);
                    } else {
                        $location   = $label;
                    }

                    $label = '';
                    try {
                        $req = \Http::init($location, 'GET');
                        $req->setRequestHeaders(array(
                            'User-Agent: ' . 'ablogcms/' . VERSION,
                            'Accept-Language: ' . HTTP_ACCEPT_LANGUAGE,
                        ));
                        $response = $req->send();
                        $label = $response->getResponseBody();
                    } catch (\Exception $e) {}
                } else {
                    $label  = '';
                }
            }

            $level      = $this->buildLevel(intval($row['id']));
            $lvLabel    = isset($levelLabel[$level]) ? $levelLabel[$level] : strval($level);
            $lvBlock    = 'level_'.strval($level);

            $vars   = array(
                'label' => $label,
                'level' => strval($this->buildLevel(intval($row['id']))),
            );
            if ( !preg_match('/^#$/', $uri) ) {
                $vars['attr']   = (substr($row['attr'], 0, 1) !== ' ' ? ' ' : '').$row['attr'];
            } else {
                $Tpl->add(array('li#front', 'navigation:loop'));
            }

            $Tpl->add(array($lvBlock, 'navigation:loop'));
            $Tpl->add('navigation:loop', $vars);

            foreach ( $row['end'] as $block ) {
                if ($block === 'ul#front') {
                    $Tpl->add(array($lvBlock, 'ul#front', 'navigation:loop'));
                    $Tpl->add(array('ul#front', 'navigation:loop'), array(
                        'ulLevel'   => $lvLabel,
                    ));
                } else {
                    $Tpl->add(array($lvBlock, $block, 'navigation:loop'));
                    $Tpl->add(array($block, 'navigation:loop'));
                }
                $Tpl->add('navigation:loop');
            }

        }
        $Tpl->add(array('ul#rear', 'navigation:loop'));
        $Tpl->add('navigation:loop');

        return setGlobalVars($Tpl->get());
    }

    function buildLevel($id, $recursive = false)
    {
        static $level = 1;
        if ( !$recursive ) {
            $level = 1;
        }

        $pid = intval($this->parentNavi[$id]);
        if ( $pid === 0 ) {
            return $level;
        }
        $level++;
        return $this->buildLevel($pid, true);
    }
}

