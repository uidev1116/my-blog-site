<?php

namespace Acms\Services\Template;

use DB;
use SQL;
use Storage;
use Tpl;
use ACMS_RAM;
use Field;
use Field_Validation;
use ACMS_Filter;
use Acms\Services\Facades\Media;
use Acms\Services\Facades\RichEditor;

class Helper
{
    /**
     * テキストインプットの組み立て
     *
     * @param array $data
     * @param Acms\Service\View\Engine $Tpl
     * @param array $block
     *
     * @return array
     */
    protected function buildInputTextValue($data, $Tpl, $block = array())
    {
        if ( !is_array($block) ) $block = array($block);
        $vars   = array();
        foreach ( $data as $key => $val ) {
            if ( is_array($val) ) {
                foreach ( $val as $i => $v ) {

                    if ( empty($i) ) {
                        $vars[$key] = $v;
                        if ( !empty($Tpl) ) {
                            $Tpl->add(array_merge(array($key), $block), array($key => $v));
                        }
                    }

                    $sfx    = '['.$i.']';
                    if ( $v !== '' ) { $vars[$key.$sfx] = $v; }
                    if ( !empty($Tpl) ) {
                        if ( !empty($i) ) {
                            $Tpl->add(array_merge(array('glue', $key.':loop'), $block));
                            $Tpl->add(array_merge(array($key.':glue', $key.':loop'), $block));
                        }
                        $Tpl->add(array_merge(array($key.':loop'), $block)
                            , !empty($v) ? array($key => $v) : array());
                    }
                }
            } else {

                //--------
                // legacy?
                $vars[$key] = $val;

            }
        }
        return $vars;
    }

    /**
     * チェックボックスインプットの組み立て
     *
     * @param array $data
     * @param Acms\Service\View\Engine & $Tpl
     * @param array $block
     *
     * @return array
     */
    protected function buildInputCheckboxChecked($data, $Tpl, $block = array())
    {
        if ( !is_array($block) ) $block = array($block);
        $vars   = array();
        foreach ( $data as $key => $vals ) {
            if ( !is_array($vals) ) $vals   = array($vals);
            foreach ( $vals as $i => $val ) {
                if ( !is_array($val) ) {
                    foreach ( array(
                        $key.':checked#'.$val,
                        $key.'['.$i.']'.':checked#'.$val,
                    ) as $name ) {
                        $vars[$name]    = config('attr_checked');
                        if ( !empty($Tpl) ) {
                            $Tpl->add(array_merge(array($name), $block));
                        }
                    }
                }
            }
        }
        return $vars;
    }

    /**
     * セレクトボックスインプットの組み立て
     *
     * @param array $data
     * @param Acms\Service\View\Engine & $Tpl
     * @param array $block
     *
     * @return array
     */
    protected function buildSelectSelected($data, $Tpl, $block = array())
    {
        if ( !is_array($block) ) $block = array($block);
        $vars   = array();
        foreach ( $data as $key => $vals ) {
            if ( !is_array($vals) ) $vals   = array($vals);
            foreach ( $vals as $i => $val ) {
                if ( !is_array($val) ) {
                    foreach ( array(
                        $key.':selected#'.$val,
                        $key.'['.$i.']'.':selected#'.$val,
                    ) as $name ) {
                        $vars[$name]    = config('attr_selected');
                        if ( !empty($Tpl) ) {
                            $Tpl->add(array_merge(array($name), $block));
                        }
                    }
                }
            }
        }
        return $vars;
    }

    /**
     * モジュールフィールドの組み立て
     *
     * @param Acms\Service\View\Engine $Tpl
     * @param int $mid
     * @param bool $show
     *
     * @return void
     */
    public function buildModuleField($Tpl, $mid = null, $show = false)
    {
        if ( $mid && $show ) {
            $vars = $this->buildField(loadModuleField($mid), $Tpl, 'moduleField');
            $Tpl->add('moduleField', $vars);
        }
    }

    /**
     * 日付の組み立て
     *
     * @param int|string $datetime
     * @param Acms\Service\View\Engine $Tpl
     * @param array $block
     * @param string $prefix
     *
     * @return array
     */
    public function buildDate($datetime, $Tpl, $block = array(), $prefix = 'date#')
    {
        if ( !is_numeric($datetime) ) $datetime = strtotime($datetime);

        $block  = empty($block) ? array() : (is_array($block) ? $block : array($block));
        $w  = date('w', $datetime);
        $weekPrefix = $prefix === 'date#' ? 'week#'
                                          : str_replace('date', 'week', $prefix);
        $Tpl->add(array_merge(array($weekPrefix.$w), $block));

        $formats = array(
            'd', 'D', 'j', 'l', 'N', 'S', 'w', 'z',
            'W',
            'F', 'm', 'M', 'n', 't',
            'L', 'o', 'Y', 'y',
            'a', 'A', 'B', 'g', 'G', 'h', 'H', 'i', 's', 'u',
            'e',
            'I', 'O', 'P', 'T', 'Z',
            'c', 'r', 'U',
        );
        $vars   = array();

        //--------
        // format
        $combined   = implode('__', $formats);
        $formatted  = explode('__', date($combined, $datetime));
        foreach ( $formatted as $p => $val ) {
            $c = $formats[$p];
            $vars[$prefix.$c] = $val;
        }
        $vars[$prefix]  = date('Y-m-d H:i:s', $datetime);

        $vars[$prefix.'week']   = config('week_label', '', intval($w));
        return $vars;
    }

    public function injectMediaField($Field, $force = false)
    {
        if (!$force && !ACMS_POST) {
            return;
        }
        $mediaIds = array();
        $mediaList = array();
        $useMediaField = array();
        foreach ($Field->listFields() as $fd) {
            if (strpos($fd, '@media') !== false) {
                $useMediaField[] = substr($fd, 0, -6);
                foreach ($Field->getArray($fd) as $mid) {
                    $mediaIds = intval($mid);
                }
            }
        }
        if ($mediaIds) {
            $DB = DB::singleton(dsn());
            $SQL = SQL::newSelect('media');
            $SQL->addWhereIn('media_id', $mediaIds);
            $q  = $SQL->get(dsn());
            $DB->query($q, 'fetch');
            while ($media = $DB->fetch($q)) {
                $mid = intval($media['media_id']);
                $mediaList[$mid] = $media;
            }
        }
        Media::injectMediaField($Field, $mediaList, $useMediaField);
    }

    public function injectRichEditorField($Field, $force = true)
    {
        if (!$force && !ACMS_POST) {
            return;
        }

        foreach ($Field->listFields() as $fd) {
            if (strpos($fd, '@html') !== false) {
                $values = $Field->getArray($fd);
                $fix = array();
                foreach ($values as $value) {
                    $fix[] = RichEditor::fix($value);
                }
                $Field->setField($fd, $fix);
            }
        }
    }

    /**
     * カスタムフィールドの組み立て
     *
     * @param ACMS_Field $Field
     * @param Acms\Service\View\Engine $Tpl
     * @param array $block
     * @param string $scp
     * @param array $loop_vars
     *
     * @return array
     */
    public function buildField($Field, $Tpl, $block = array(), $scp = null, $loop_vars=array())
    {
        $block  = !empty($block) ? (is_array($block) ? $block : array($block)) : array();
        $vars   = array();
        $this->injectMediaField($Field);
        $this->injectRichEditorField($Field);
        $fds    = $Field->listFields(true);

        $isSearch   = ('FIELD_SEARCH' == strtoupper(get_class($Field))) ? true : false;

        //-------
        // group
        $mapGroup   = array();
        foreach ( $Field->listFields() as $fd ) {
            if (preg_match('/^@(.*)$/', $fd, $match)) {
                $groupName = $match[1];
                $mapGroup[$groupName] = $Field->getArray($fd);
            }
        }
        foreach ( $mapGroup as $groupName => $aryFd ) {
            $data   = array();
            for ( $i=0; true; $i++ ) {
                $row        = array();
                $isExists   = false;
                $hasValidator = false;
                foreach ( $aryFd as $fd ) {
                    $isExists |= $Field->isExists($fd, $i);
                    $row[$fd] = $Field->get($fd, '', $i);
                    if ($Field->isExists($fd . '@media', $i)) {
                        foreach (array('name', 'fileSize', 'caption', 'link', 'alt', 'text', 'path', 'thumbnail', 'imageSize', 'type', 'extension') as $exMedia) {
                            $fdMedia = $fd . "@$exMedia";
                            $row[$fdMedia] = $Field->get($fdMedia, '', $i);
                        }
                    }

                    if ($Field->isExists($fd. '@html', $i)) {
                        $row[$fd. '@html'] = $Field->get($fd. '@html', '', $i);
                    }

                    if ($Field->isExists($fd. '@title', $i)) {
                        $row[$fd. '@title'] = $Field->get($fd. '@title', '', $i);
                    }

                    if ( !$hasValidator && method_exists($Field, 'isValid') ) {
                        $validator = $Field->getMethods($fd);
                        $hasValidator |= !empty($validator);
                    }
                }
                if ( !$isExists ) { break; }
                // 空の行を削除するかどうか
                if (!$hasValidator) {
                    if ( !join('', $row) ) { continue; }
                }
                $data[] = $row;
            }

            foreach ( $data as $i => $row ) {
                $_vars      = $loop_vars;
                $loopblock  = array_merge(array($groupName.':loop'), $block);

                //-----------
                // validator
                if ( method_exists($Field, 'isValid') ) {
                    foreach ( $row as $fd => $kipple ) {
                        foreach ( $Field->getMethods($fd) as $method ) {
                            if ( !$val = intval($Field->isValid($fd, $method, $i)) ) {
                                foreach ( array('validator', 'v') as $v ) {
                                    $key    = $fd.':'.$v.'#'.$method;
                                    $_vars[$key] = $val;
                                    $Tpl->add(array_merge(array($key), $loopblock), array($key => $val));
                                }
                            }
                        }
                    }
                }

                //-------
                // value
                foreach ( $row as $key => $value ) {
                    if ( $value !== '' ) {
                        $_vars[$key]    = $value;
                        $_vars[$key.':checked#'.$value]     = config('attr_checked');
                        $_vars[$key.':selected#'.$value]    = config('attr_selected');
                    }
                    if ( !empty($i) ) {
                        $Tpl->add(array_merge(array($key.':glue'), $loopblock));
                    }
                }

                //---
                // n
                $_vars['i'] = $i;

                if ( !empty($i) ) {
                    $Tpl->add(array_merge(array('glue'), $loopblock));
                    $Tpl->add(array_merge(array($groupName.':glue'), $loopblock));
                }
                $Tpl->add($loopblock, $_vars);
            }
        }

        $data   = array();
        foreach ( $fds as $fd ) {
            if ( !$aryVal = $Field->getArray($fd) ) $Tpl->add(array_merge(array($fd.':null'), $block));
            $data[$fd]  = $aryVal;
            if ( $isSearch ) {
                $data[$fd.'@connector'] = $Field->getConnector($fd, null);
                $data[$fd.'@operator']  = $Field->getOperator($fd, null);
            }
            if ( !method_exists($Field, 'isValid') ) continue;
            if ( !$val = intval($Field->isValid($fd)) ) {
                foreach ( array('validator', 'v') as $v ) {
                    $key    = $fd.':'.$v;
                    $vars[$key] = $val;
                    $Tpl->add(array_merge(array($key), $block), array($key => $val));
                }

                $aryMethod  = $Field->getMethods($fd);
                foreach ( $aryMethod as $method ) {
                    if ( !$val = intval($Field->isValid($fd, $method)) ) {
                        foreach ( array('validator', 'v') as $v ) {
                            $key    = $fd.':'.$v.'#'.$method;
                            $vars[$key] = $val;
                            $Tpl->add(array_merge(array($key), $block), array($key => $val));
                        }

                        $cnt    = count($Field->getArray($fd));
                        for ( $i=0; $i<$cnt; $i++ ) {
                            if ( !$val = intval($Field->isValid($fd, $method, $i)) ) {
                                foreach ( array('validator', 'v') as $v ) {
                                    $key    = $fd.'['.$i.']'.':'.$v.'#'.$method;
                                    $vars[$key] = $val;
                                    $Tpl->add(array_merge(array($key), $block), array($key => $val));
                                }
                            } else {
                                continue;
                            }
                        }
                    } else {
                        continue;
                    }
                }
            } else {
                continue;
            }
        }
        //-------
        // touch
        foreach ( $data as $fd => $vals ) {
            if ( !is_array($vals) ) {
                $vals   = array($vals);
            }
            foreach ( $vals as $i => $val ) {
                if ( empty($i) ) {
                    if ( !is_array($val) ) {
                        $Tpl->add(array_merge(array($fd.':touch#'.$val), $block));
                    }
                }
                if ( !is_array($val) ) {
                    $Tpl->add(array_merge(array($fd.'['.$i.']'.':touch#'.$val), $block));
                }
            }
        }

        $vars   += $this->buildInputTextValue($data, $Tpl, $block);
        $vars   += $this->buildInputCheckboxChecked($data, $Tpl, $block);
        $vars   += $this->buildSelectSelected($data, $Tpl, $block);

        if ( !is_null($scp) ) $vars[(!empty($scp) ? $scp.':' : '').'takeover'] = acmsSerialize($Field);


        foreach ( $Field->listChildren() as $child ) {
            $vars += $this->buildField($Field->getChild($child), $Tpl, $block, $child);
        }

        return $vars;
    }

    /**
     * ページャーの組み立て
     *
     * @param int $page ページ数
     * @param int $limit 1ページの件数
     * @param int $amount 総数
     * @param int $delta 前後ページ数
     * @param string $curAttr
     * @param Acms\Service\View\Engine $Tpl
     * @param array $block
     * @param array $Q
     *
     * @return array
     */
    public function buildPager($page, $limit, $amount, $delta, $curAttr, $Tpl, $block = array(), $Q=array())
    {
        $vars   = array();
        $block  = is_array($block) ? $block : array($block);

        $from   = ($page - 1) * $limit;
        $to     = $from + $limit;// - 1;
        if ( $amount < $to ) {
            $to = $amount;
        }
        $vars   += array(
            'itemsAmount'    => $amount,
            'itemsFrom'      => $from + 1,
            'itemsTo'        => $to,
        );
        $lastPage   = ceil($amount/$limit);
        $fromPage   = 1 > ($page - $delta) ? 1 : ($page - $delta);
        $toPage     = $lastPage < ($page + $delta) ? $lastPage : ($page + $delta);

        if ( $lastPage > 1 ) {
            for ( $curPage=$fromPage; $curPage<=$toPage; $curPage++ ) {
                $_vars  = array('page' => $curPage);
                if ( $curPage <> $toPage ) {
                    $Tpl->add(array_merge(array('glue', 'page:loop'), $block));
                }
                if ( PAGE == $curPage ) {
                    $_vars['pageCurAttr']    = $curAttr;
                } else {
                    $Tpl->add(array_merge(array('link#front', 'page:loop'), $block), array(
                        'url'   => acmsLink($Q + array(
                            'page'      => $curPage,
                        )),
                    ));
                    $Tpl->add(array_merge(array('link#rear', 'page:loop'), $block));
                }
                $Tpl->add(array_merge(array('page:loop'), $block), $_vars);
            }
        }

        if ( $toPage <> $lastPage ) {
            $vars   += array(
                'lastPageUrl'   => acmsLink($Q + array(
                    'page'      => $lastPage,
                )),
                'lastPage'  => $lastPage,
            );
        }

        if ( 1 < $fromPage ) {
            $vars   += array(
                'firstPageUrl'   => acmsLink($Q + array(
                    'page'      => 1,
                )),
                'firstPage'  => 1,
            );
        }

        if ( 1 < $page ) {
            $Tpl->add(array_merge(array('backLink'), $block), array(
                'url' => acmsLink($Q + array(
                    'page'      => ($page > 2) ? $page - 1 : false,
                )),
                'backNum'   => $limit,
                'backPage'  => ($page > 1) ? $page - 1 : false,
            ));
        }
        if ( $page <> $lastPage ) {
            $forwardNum = $amount - ($from + $limit);
            if ( $limit < $forwardNum ) $forwardNum = $limit;
            $Tpl->add(array_merge(array('forwardLink'), $block), array(
                'url' => acmsLink($Q + array(
                    'page'      => $page + 1,
                )),
                'forwardNum'    => $forwardNum,
                'forwardPage'   => $page + 1,
            ));
        }

        return $vars;
    }

    /**
     * フルテキストのEagerLoading
     *
     * @param $entries array
     * @return array
     */
    public function eagerLoadFullText($entries)
    {
        $eagerLoadingData = array();
        if (empty($entries)) {
            return $eagerLoadingData;
        }
        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('column');
        $SQL->addWhereIn('column_entry_id', array_unique($entries));
        $SQL->addWhereOpr('column_attr', 'acms-form', '<>');
        $SQL->addOrder('column_sort', 'ASC');
        $q = $SQL->get(dsn());
        $DB->query($q, 'fetch');
        while ($unit = $DB->fetch($q)) {
            $eid = $unit['column_entry_id'];
            if (!isset($eagerLoadingData[$eid])) {
                $eagerLoadingData[$eid] = array();
            }
            $eagerLoadingData[$eid][] = $unit;
        }
        return $eagerLoadingData;
    }

    /**
     * フルテキストの組み立て
     *
     * @param array $vars
     * @param int $eid
     * @param array $eagerLoadingData
     *
     * @return array
     */
    public function buildSummaryFulltext($vars, $eid, $eagerLoadingData)
    {
        $textData = array();

        if (isset($eagerLoadingData[$eid]) && is_array($eagerLoadingData[$eid])) {
            foreach ($eagerLoadingData[$eid] as $unit) {
                if ($unit['column_align'] === 'hidden') continue;
                $type = detectUnitTypeSpecifier($unit['column_type']);
                if ('text' === $type) {
                    $_text  = $unit['column_field_1'];
                    $corrector = new \ACMS_Corrector();
                    switch ($unit['column_field_2']) {
                        case 'markdown':
                            $_text = \Michelf\MarkdownExtra::defaultTransform($_text);
                            break;
                        case 'table':
                            $_text = $corrector->table($_text);
                            break;
                    }
                    $text = preg_replace('@\s+@u', ' ', strip_tags($_text));
                    $data = explodeUnitData($text);
                    foreach ($data as $i => $txt) {
                        if (isset($textData[$i])) {
                            $textData[$i] .= $txt.' ';
                        } else {
                            $textData[] = $txt.' ';
                        }
                    }
                } else if ('rich-editor' === $type) {
                    $_text  = $unit['column_field_1'];
                    $html = RichEditor::render($_text);
                    $text = strip_tags($html);
                    $data = explodeUnitData($text);
                    foreach ($data as $i => $txt) {
                        if (isset($textData[$i])) {
                            $textData[$i] .= $txt.' ';
                        } else {
                            $textData[] = $txt.' ';
                        }
                    }
                }
            }
        }
        buildUnitData($textData, $vars, 'summary');

        return $vars;
    }

    /**
     * タグのEagerLoading
     *
     * @param $eidArray array
     * @return array
     */
    public function eagerLoadTag($eidArray)
    {
        $eagerLoadingData = array();
        if (empty($eidArray)) {
            return $eagerLoadingData;
        }
        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('tag');
        $SQL->addWhereIn('tag_entry_id', $eidArray);
        $SQL->addOrder('tag_sort');
        $q = $SQL->get(dsn());
        $DB->query($q, 'fetch');
        while ($tag = $DB->fetch($q)) {
            $eid = intval($tag['tag_entry_id']);
            if (!isset($eagerLoadingData[$eid])) {
                $eagerLoadingData[$eid] = array();
            }
            $eagerLoadingData[$eid][] = $tag;
        }
        return $eagerLoadingData;
    }

    /**
     * 関連記事のEagerLoading
     *
     * @param $eidArray array
     * @return array
     */
    public function eagerLoadRelatedEntry($eidArray)
    {
        $eagerLoadingData = array();
        if (empty($eidArray)) {
            return $eagerLoadingData;
        }
        $db = DB::singleton(dsn());
        $sql = SQL::newSelect('relationship');
        $sql->addLeftJoin('entry', 'entry_id', 'relation_eid');
        ACMS_Filter::entrySession($sql);
        $sql->addWhereIn('relation_id', $eidArray);
        $sql->setOrder('relation_order', 'ASC');
        $relations = $db->query($sql->get(dsn()), 'all');

        $entryIds = array();
        foreach ($relations as $relation) {
            $entryIds[] = $relation['relation_eid'];
        }
        $eagerLoadingEntry = eagerLoadEntry($entryIds);
        $eagerLoadingField = eagerLoadField($entryIds, 'eid');

        foreach ($relations as $relation) {
            $eid = $relation['relation_id'];
            $type = $relation['relation_type'];
            if (!isset($eagerLoadingData[$eid])) {
                $eagerLoadingData[$eid] = array();
            }
            if (!isset($eagerLoadingData[$eid][$type])) {
                $eagerLoadingData[$eid][$type] = array();
            }
            $targetEid = $relation['relation_eid'];
            $data = isset($eagerLoadingEntry[$targetEid]) ? $eagerLoadingEntry[$targetEid] : array('eid' => $targetEid);
            $data['field'] = isset($eagerLoadingField[$targetEid]) ? $eagerLoadingField[$targetEid] : null;
            $eagerLoadingData[$eid][$type][] = $data;
        }
        return $eagerLoadingData;
    }

    /**
     * タグの組み立て
     *
     * @param Acms\Service\View\Engine $tpl
     * @param int $eid
     * @param array $eagerLoadingData
     */
    public function buildTag($tpl, $eid, $eagerLoadingData)
    {
        if (isset($eagerLoadingData[$eid]) && is_array($eagerLoadingData[$eid])) {
            $length = count($eagerLoadingData[$eid]);
            foreach ($eagerLoadingData[$eid] as $i => $tag) {
                if ($length > ($i + 1)) $tpl->add(array('tagGlue', 'tag:loop'));
                $tpl->add('tag:loop', array(
                    'name'  => $tag['tag_name'],
                    'url'   => acmsLink(array(
                        'bid'   => $tag['tag_blog_id'],
                        'tag'   => $tag['tag_name'],
                    )),
                ));
            }
        }
    }

    /**
     * メインイメージのEagerLoading
     *
     * @param $entries
     * @return array
     */
    function eagerLoadMainImage($entries)
    {
        $eagerLoadingData = array(
            'unit' => array(),
            'media' => array(),
        );
        $mainImageUnits = array();
        $mediaIds = array();
        foreach ($entries as $entry) {
            $primaryImageUnitId = intval($entry['entry_primary_image']);
            if (empty($primaryImageUnitId)) {
                continue;
            }
            $mainImageUnits[] = $primaryImageUnitId;
        }
        if (empty($mainImageUnits)) {
            return $eagerLoadingData;
        }
        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('column');
        $SQL->addWhereIn('column_id', array_unique($mainImageUnits));
        $q = $SQL->get(dsn());
        $DB->query($q, 'fetch');
        while ($unit = $DB->fetch($q)) {
            $utid = $unit['column_id'];
            $type = detectUnitTypeSpecifier($unit['column_type']);
            $paths = array();
            $alt = '';
            $caption = '';
            if ($type === 'image') {
                $paths = explodeUnitData($unit['column_field_2']);
                $alt = explodeUnitData($unit['column_field_4']);
                $caption = explodeUnitData($unit['column_field_1']);
            } else if ($type === 'media') {
                $paths = explodeUnitData($unit['column_field_1']);
                $alt = explodeUnitData($unit['column_field_3']);
                $caption = explodeUnitData($unit['column_field_2']);
                $mediaIds = array_merge($paths, $mediaIds);
            }
            $eagerLoadingData['unit'][$utid] = $unit;
            $eagerLoadingData['unit'][$utid] += array(
                'type' => $type,
                'paths' => $paths,
                'alt' => $alt,
                'caption' => $caption,
            );
        }
        $SQL = SQL::newSelect('media');
        $SQL->addWhereIn('media_id', $mediaIds);
        $q = $SQL->get(dsn());
        $DB->query($q, 'fetch');
        while ($media = $DB->fetch($q)) {
            $mediaId = $media['media_id'];
            $eagerLoadingData['media'][$mediaId] = $media;
        }
        return $eagerLoadingData;
    }

    /**
     * 画像の組み立て
     *
     * @param Acms\Service\View\Engine $Tpl
     * @param int $pimageId
     * @param array $config
     * @param array $eagerLoadingData
     *
     * @return array
     */
    public function buildImage($Tpl, $pimageId, $config, $eagerLoadingData)
    {
        $pathAry = array();
        $vars = array();
        $squareSize = config('image_size_square');
        $unitType = 'image';

        if ($pimageId && isset($eagerLoadingData['unit'][$pimageId])) {
            $unit = $eagerLoadingData['unit'][$pimageId];
            $pathAry = $unit['paths'];
            $unitType = $unit['type'];
            $align = $unit['column_align'];
            $alt = $unit['alt'];
            $caption = $unit['caption'];

        } else {
            $path = null;
        }
        if (empty($pathAry)) {
            $Tpl->add('noimage', array(
                'noImgX'  => $config['imageX'],
                'noImgY'  => $config['imageY'],
            ));
            return array(
                'x' => $config['imageX'],
                'y' => $config['imageY'],
            );
        }
        foreach ( $pathAry as $i => $path ) {
            if ($i == 0) $fx = '';
            else $fx = ++$i;

            $vars['focalX' . $fx] = 0;
            $vars['focalY' . $fx] = 0;

            if ($unitType === 'media') {
                if (isset($eagerLoadingData['media'][$path])) {
                    if ($media = $eagerLoadingData['media'][$path]) {
                        $path = $media['media_path'];
                        $focalPoint = $media['media_field_5'];
                        if (strpos($focalPoint, ',') !== false) {
                            list($focalX, $focalY) = explode(',', $focalPoint);
                            if ($focalX && $focalY) {
                                $vars['focalX' . $fx] = (($focalX / 50) - 1);
                                $vars['focalY' . $fx] = ((($focalY / 50) - 1) * -1);
                            }
                        }
                    }
                } else {
                    continue;
                }
            }
            $storageDir = $unitType === 'image' ? ARCHIVES_DIR : MEDIA_LIBRARY_DIR;
            $filename = $path;
            $path = $storageDir . $path;
            if ( $align === 'hidden' ) $path = null;

            //-------------------
            // image is readble?
            if ( Storage::isReadable($path) ) {
                list($x, $y)    = Storage::getImageSize($path);

                if ( max($config['imageX'], $config['imageY']) > max($x, $y) ) {
                    $_path  = preg_replace('@(.*?)([^/]+)$@', '$1large-$2',  $path);
                    if ( $xy = Storage::getImageSize($_path) ) {
                        $path   = $_path;
                        $x      = $xy[0];
                        $y      = $xy[1];
                    }
                }

                $vars   += array(
                    'path'.$fx  => $path,
                );
                if ( 'on' == $config['imageTrim'] ) {
                    if ( $x > $config['imageX'] and $y > $config['imageY'] ) {
                        if ( ($x / $config['imageX']) < ($y / $config['imageY']) ) {
                            $imgX   = $config['imageX'];
                            $imgY   = @round($y / ($x / $config['imageX']));
                        } else {
                            $imgY   = $config['imageY'];
                            $imgX   = @round($x / ($y / $config['imageY']));
                        }
                    } else {
                        if ( $x < $config['imageX'] ) {
                            $imgX   = $config['imageX'];
                            $imgY   = @round($y * ($config['imageX'] / $x));
                        } else if ( $y < $config['imageY'] ) {
                            $imgY   = $config['imageY'];
                            $imgX   = @round($x * ($config['imageY'] / $y));
                        } else {
                            if ( ($config['imageX'] - $x) > ($config['imageY'] - $y) ) {
                                $imgX   = $config['imageX'];
                                $imgY   = @round($y * ($config['imageX'] / $x));
                            } else {
                                $imgY   = $config['imageY'];
                                $imgX   = @round($x * ($config['imageY'] / $y));
                            }
                        }
                    }
                    $config['imageCenter']  = 'on';
                } else {
                    if ( $x > $config['imageX'] ) {
                        if ( $y > $config['imageY'] ) {
                            if ( ($x - $config['imageX']) < ($y - $config['imageY']) ) {
                                $imgY   = $config['imageY'];
                                $imgX   = @round($x / ($y / $config['imageY']));
                            } else {
                                $imgX   = $config['imageX'];
                                $imgY   = @round($y / ($x / $config['imageX']));
                            }
                        } else {
                            $imgX   = $config['imageX'];
                            $imgY   = round($y / ($x / $config['imageX']));
                        }
                    } else if ( $y > $config['imageY'] ) {
                        $imgY   = $config['imageY'];
                        $imgX   = round($x / ($y / $config['imageY']));
                    } else {
                        if ( 'on' == $config['imageZoom'] ) {
                            if ( ($config['imageX'] - $x) > ($config['imageY'] - $y) ) {
                                $imgY   = $config['imageY'];
                                $imgX   = round($x * ($config['imageY'] / $y));
                            } else {
                                $imgX   = $config['imageX'];
                                $imgY   = round($y * ($config['imageX'] / $x));
                            }
                        } else {
                            $imgX   = $x;
                            $imgY   = $y;
                        }
                    }
                }
                //-------
                // align
                if ( 'on' == $config['imageCenter'] ) {
                    if ( $imgX > $config['imageX'] ) {
                        $left   = round((-1 * ($imgX - $config['imageX'])) / 2);
                    } else {
                        $left   = round(($config['imageX'] - $imgX) / 2);
                    }
                    if ( $imgY > $config['imageY'] ) {
                        $top    = round((-1 * ($imgY - $config['imageY'])) / 2);
                    } else {
                        $top    = round(($config['imageY'] - $imgY) / 2);
                    }
                } else {
                    $left   = 0;
                    $top    = 0;
                }

                $vars += array(
                    'imgX'.$fx  => $imgX,
                    'imgY'.$fx  => $imgY,
                    'left'.$fx  => $left,
                    'top'.$fx   => $top,
                    'alt'.$fx   => $alt,
                    'caption'.$fx => $caption
                );
                //------
                // tiny
                $tiny   = $storageDir.preg_replace('@(.*?)([^/]+)$@', '$1tiny-$2', $filename);
                if ( $xy = Storage::getImageSize($tiny) ) {
                    $vars   += array(
                        'tinyPath'.$fx  => $tiny,
                        'tinyX'.$fx     => $xy[0],
                        'tinyY'.$fx     => $xy[1],
                    );
                }
                //--------
                // square
                $square = $storageDir.preg_replace('@(.*?)([^/]+)$@', '$1square-$2', $filename);
                if ( Storage::isFile($square) ) {
                    $vars   += array(
                        'squarePath'.$fx    => $square,
                        'squareX'.$fx       => $squareSize,
                        'squareY'.$fx       => $squareSize,
                    );
                }
                //--------
                // large
                $large = $storageDir.preg_replace('@(.*?)([^/]+)$@', '$1large-$2', $filename);
                if ( $xy = Storage::getImageSize($large) ) {
                    $vars   += array(
                        'largePath'.$fx    => $large,
                        'largeX'.$fx       => $xy[0],
                        'largeY'.$fx       => $xy[1],
                    );
                }

            } else {
                $Tpl->add('noimage', array(
                    'noImgX'  => $config['imageX'],
                    'noImgY'  => $config['imageY'],
                ));
            }
            $vars   += array(
                'x'.$fx => $config['imageX'],
                'y'.$fx => $config['imageY'],
            );
        }
        return $vars;
    }

    /**
     * 関連記事を組み立て
     *
     * @param $Tpl
     * @param $eid
     * @param $eagerLoadingData
     * @param array $block
     */
    public function buildRelatedEntriesList($Tpl, $eid, $eagerLoadingData, $block = array())
    {
        $block = !empty($block) ? (is_array($block) ? $block : array($block)) : array();

        if (!isset($eagerLoadingData[$eid])) {
            $Tpl->add($block);
            return;
        }
        $eagerLoading = $eagerLoadingData[$eid];

        foreach ($eagerLoading as $type => $data) {
            $typeBlock = 'relatedEntry.' . $type;
            $loopBlock = array_merge(array($typeBlock . ':loop', $typeBlock), $block);
            foreach ($data as $entry) {
                $field = $entry['field'];
                $vars = array(
                    'bid' => $entry['bid'],
                    'cid' => $entry['cid'],
                    'uid' => $entry['uid'],
                    'eid' => $entry['eid'],
                    'title' => $entry['title'],
                    'url' => $entry['url'],
                    'categoryName' => ACMS_RAM::categoryName($entry['cid']),
                );
                $vars += $this->buildField($field, $Tpl, $loopBlock);
                $Tpl->add($loopBlock, $vars);
            }
            $Tpl->add(array_merge(array($typeBlock), $block));
        }
    }

    /**
     * 関連記事の組み立て
     *
     * @param Acms\Service\View\Engine $Tpl
     * @param array $eids
     * @param array $block
     * @param string $start
     * @param string $end
     *
     * @return void
     */
    public function buildRelatedEntries($Tpl, $eids, $block, $start, $end, $relatedBlock = 'related:loop')
    {
        $block      = !empty($block) ? (is_array($block) ? $block : array($block)) : array();
        $loopblock  = array_merge(array($relatedBlock), $block);

        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('entry');
        $SQL->addWhereIn('entry_id', $eids);
        ACMS_Filter::entrySpan($SQL, $start, $end);
        ACMS_Filter::entrySession($SQL);
        $SQL->setFieldOrder('entry_id', $eids);
        $all    = $DB->query($SQL->get(dsn()), 'all');

        $mainImages = $this->eagerLoadMainImage($all);
        $eagerLoadField = eagerLoadField($eids, 'eid');
        $config = array(
            'imageX' => 100,
            'imageY' => 100,
            'imageTrim' => 'off',
            'imageCenter' => 'off',
        );

        foreach ( $all as $row ) {
            $bid    = intval($row['entry_blog_id']);
            $cid    = intval($row['entry_category_id']);
            $eid    = intval($row['entry_id']);
            $vars   = array(
                'related.eid'           => $eid,
                'related.bid'           => $bid,
                'related.cid'           => $cid,
                'related.categoryName'  => ACMS_RAM::categoryName($cid),
                'related.permalink' => acmsLink(array(
                    'bid'   => $bid,
                    'cid'   => $cid,
                    'eid'   => $eid,
                ), false),
            );
            if (isset($row['entry_primary_image']) && $row['entry_primary_image']) {
                $images = $this->buildImage($Tpl, $row['entry_primary_image'], $config, $mainImages);
                foreach ($images as $key => $val) {
                    $vars['related.' . $key] = $val;
                }
            }
            $title  = addPrefixEntryTitle($row['entry_title']
                , $row['entry_status']
                , $row['entry_start_datetime']
                , $row['entry_end_datetime']
                , $row['entry_approval']
            );
            $vars['related.title']  = $title;
            $link   = $row['entry_link'];
            $url    = acmsLink(array(
                'bid'   => $bid,
                'cid'   => $cid,
                'eid'   => $eid,
            ));
            if ( $link != '#' ) {
                $vars['related.url']  = !empty($link) ? $link : $url;
            }
            if (isset($field[$eid])) {
                $vars += $this->buildField($eagerLoadField[$eid], $Tpl, array_merge(array('relatedAdminField', $relatedBlock), $block));
            }
            $Tpl->add($loopblock, $vars);
        }
    }

    /**
     * サマリーの組み立て
     *
     * @param Acms\Service\View\Engine $Tpl
     * @param array $row
     * @param int $count
     * @param int $gluePoint
     * @param array $config
     * @param array $extraVars
     * @param int $page
     * @param array $eagerLoadingData
     *
     * @return void
     */
    function buildSummary($Tpl, $row, $count, $gluePoint, $config, $extraVars = array(), $page = 1, $eagerLoadingData = array())
    {
        if ( $row && isset($row['entry_id']) ) {
            if ( !IS_LICENSED ) $row['entry_title'] = '[test]'.$row['entry_title'];

            $bid    = intval($row['entry_blog_id']);
            $uid    = intval($row['entry_user_id']);
            $cid    = intval($row['entry_category_id']);
            $eid    = intval($row['entry_id']);
            $clid   = intval($row['entry_primary_image']);
            $sort   = intval($row['entry_sort']);
            $csort  = intval($row['entry_category_sort']);
            $usort  = intval($row['entry_user_sort']);

            $ecd    = $row['entry_code'];
            $link   = $row['entry_link'];
            $status = $row['entry_status'];
            $permalink  = acmsLink(array(
                'bid'   => $bid,
                'cid'   => $cid,
                'eid'   => $eid,
            ), false);
            $url        = acmsLink(array(
                'bid'   => $bid,
                'cid'   => $cid,
                'eid'   => $eid,
            ));
            $title  = addPrefixEntryTitle($row['entry_title']
                , $status
                , $row['entry_start_datetime']
                , $row['entry_end_datetime']
                , $row['entry_approval']
            );

            if ( $count % 2 == 0 ) {
                $oddOrEven  = 'even';
            } else {
                $oddOrEven  = 'odd';
            }

            $vars   = array(
                'permalink'     => $permalink,
                'title'         => $title,
                'eid'           => $eid,
                'ecd'           => $ecd,
                'uid'           => $uid,
                'bid'           => $bid,
                'sort'          => $sort,
                'csort'         => $csort,
                'usort'         => $usort,
                'iNum'          => $count,
                'sNum'          => (($page - 1) * $config['limit']) + $count,
                'oddOrEven'     => $oddOrEven,
                'status'        => $status,
                'geo_distance'  => isset($row['distance']) ? $row['distance'] : '',
                'geo_zoom'      => isset($row['geo_zoom']) ? $row['geo_zoom'] : '',
                'geo_lat'       => isset($row['latitude']) ? $row['latitude'] : '',
                'geo_lng'       => isset($row['longitude']) ? $row['longitude'] : '',
                'entry:loop.class' => isset($config['loop_class']) ? $config['loop_class'] : '',
            );

            if ( $link != '#' ) {
                $vars += array(
                    'url' => !empty($link) ? $link : $url,
                );
                $Tpl->add('url#rear');
            }

            if ( !isset($config['blogInfoOn']) or $config['blogInfoOn'] === 'on' ) {
                $blogName   = $row['blog_name'];
                $blogCode   = $row['blog_code'];
                $blogUrl    = acmsLink(array(
                    'bid'   => $bid,
                ));
                $vars   += array(
                    'blogName'  => $blogName,
                    'blogCode'  => $blogCode,
                    'blogUrl'   => $blogUrl,
                );
            }

            if ( !empty($cid) and (!isset($config['categoryInfoOn']) or $config['categoryInfoOn'] === 'on')) {
                $categoryName   = $row['category_name'];
                $categoryCode   = $row['category_code'];
                $categoryUrl    = acmsLink(array(
                    'bid'   => $bid,
                    'cid'   => $cid,
                ));

                $vars   += array(
                    'categoryName'  => $categoryName,
                    'categoryCode'  => $categoryCode,
                    'categoryUrl'   => $categoryUrl,
                    'cid'           => $cid,
                );
            }

            //----------------------
            // attachment vars
            foreach ( $extraVars as $key => $val ) {
                if ( !empty($row[$val]) ) {
                    $vars   += array($key => $row[$val]);
                }
            }

            //-----
            // new
            if ( requestTime() <= strtotime($row['entry_datetime']) + intval($config['newtime']) ) {
                $Tpl->add('new');
            }

            //-------
            // image
            if (isset($eagerLoadingData['mainImage'])) {
                $vars += $this->buildImage($Tpl, $clid, $config, $eagerLoadingData['mainImage']);
            }

            //---------------
            // related entry
            if (isset($eagerLoadingData['relatedEntry'])) {
                $this->buildRelatedEntriesList($Tpl, $eid, $eagerLoadingData['relatedEntry'], array('relatedEntry', 'entry:loop'));
            } else {
                $Tpl->add(array('relatedEntry', 'entry:loop'));
            }

            //----------
            // fulltext
            if (isset($eagerLoadingData['fullText'])) {
                $vars = $this->buildSummaryFulltext($vars, $eid, $eagerLoadingData['fullText']);
                if ( 1
                    && isset($vars['summary'])
                    && isset($config['fulltextWidth'])
                    && !empty($config['fulltextWidth'])
                ) {
                    $width  = intval($config['fulltextWidth']);
                    $marker = isset($config['fulltextMarker']) ? $config['fulltextMarker'] : '';
                    $vars['summary'] = mb_strimwidth($vars['summary'], 0, $width, $marker, 'UTF-8');
                }
            }

            //------
            // date
            $vars += $this->buildDate($row['entry_datetime'], $Tpl, 'entry:loop');
            if ( !isset($config['detailDateOn']) or $config['detailDateOn'] === 'on' ) {
                $vars += $this->buildDate($row['entry_updated_datetime'], $Tpl, 'entry:loop', 'udate#');
                $vars += $this->buildDate($row['entry_posted_datetime'], $Tpl, 'entry:loop', 'pdate#');
                $vars += $this->buildDate($row['entry_start_datetime'], $Tpl, 'entry:loop', 'sdate#');
                $vars += $this->buildDate($row['entry_end_datetime'], $Tpl, 'entry:loop', 'edate#');
            }

            //-------------
            // entry field
            if (isset($eagerLoadingData['entryField'][$eid])) {
                $vars += $this->buildField($eagerLoadingData['entryField'][$eid], $Tpl);
            }

            //-------------
            // user field
            if (isset($config['userInfoOn']) && $config['userInfoOn'] === 'on') {
                if ($config['userFieldOn'] === 'on' && isset($eagerLoadingData['userField'][$uid])) {
                    $Field = $eagerLoadingData['userField'][$uid];
                } else {
                    $Field = new Field();
                }
                $Field->setField('fieldUserName', ACMS_RAM::userName($uid));
                $Field->setField('fieldUserCode', ACMS_RAM::userCode($uid));
                $Field->setField('fieldUserStatus', ACMS_RAM::userStatus($uid));
                $Field->setField('fieldUserMail', ACMS_RAM::userMail($uid));
                $Field->setField('fieldUserMailMobile', ACMS_RAM::userMailMobile($uid));
                $Field->setField('fieldUserUrl', ACMS_RAM::userUrl($uid));
                $Field->setField('fieldUserIcon', loadUserIcon($uid));
                if ( $large = loadUserLargeIcon($uid) ) {
                    $Field->setField('fieldUserLargeIcon', $large);
                }
                $Tpl->add('userField', $this->buildField($Field, $Tpl));
            }

            //------------
            // blog field
            if (isset($config['blogInfoOn']) && $config['blogInfoOn'] === 'on') {
                if ($config['blogFieldOn'] === 'on' && isset($eagerLoadingData['blogField'][$bid])) {
                    $Field = $eagerLoadingData['blogField'][$bid];
                } else {
                    $Field = new Field();
                }
                $Field->setField('fieldBlogName', $blogName);
                $Field->setField('fieldBlogCode', $blogCode);
                $Field->setField('fieldBlogUrl', $blogUrl);
                $Tpl->add('blogField', $this->buildField($Field, $Tpl));
            }

            //----------------
            // category field
            if (!empty($cid) && isset($config['categoryInfoOn']) && $config['categoryInfoOn'] === 'on') {
                if ($config['categoryFieldOn'] === 'on' && isset($eagerLoadingData['categoryField'][$cid])) {
                    $Field = $eagerLoadingData['categoryField'][$cid];
                } else {
                    $Field = new Field();
                }
                $Field->setField('fieldCategoryName', $categoryName);
                $Field->setField('fieldCategoryCode', $categoryCode);
                $Field->setField('fieldCategoryUrl', $categoryUrl);
                $Field->setField('fieldCategoryId', $cid);
                $Tpl->add(array('categoryField', 'entry:loop'), $this->buildField($Field, $Tpl, array('categoryField', 'entry:loop')));
            }

            //--------------
            // sub category
            if (isset($eagerLoadingData['subCategory'])) {
                if (isset($eagerLoadingData['subCategory'][$eid])) {
                    $subCategories = $eagerLoadingData['subCategory'][$eid];
                    foreach ($subCategories as $i => $category) {
                        if ($i !== count($subCategories) - 1) {
                            $Tpl->add(array('glue', 'sub_category:loop'));
                        }
                        $Tpl->add('sub_category:loop', array(
                            'name'  => $category['category_name'],
                            'code'  => $category['category_code'],
                            'url'   => acmsLink(array(
                                'cid'   => $category['category_id'],
                            )),
                        ));
                    }
                }
            }

            //-----
            // tag
            if (isset($eagerLoadingData['tag']) ) {
                $this->buildTag($Tpl, $eid, $eagerLoadingData['tag']);
            }

            //------
            // glue
            $addend = ($count === $gluePoint);
            if ( !$addend ) {
                $Tpl->add(array_merge(array('glue', 'entry:loop')));
            }
            $Tpl->add('entry:loop', $vars);

            if ( $addend ) {
                $Tpl->add('unit:loop');
            } else if ( $count != 0 && $config['unit'] > 0 ) {
                if ( !($count % $config['unit']) ) {
                    $Tpl->add('unit:loop');
                }
            }
        }
    }

    /**
     * 編集ページのユニットのデフォルト値を取得
     *
     * @param string $mode
     * @param string $type
     * @param int $i
     *
     * @return array
     */
    public function getAdminColumnDefinition($mode, $type, $i)
    {
        $pfx    = 'column_def_'.$mode.'_';

        // 特定指定子を除外した、一般名のユニット種別
        $type = detectUnitTypeSpecifier($type);

        if ( 'text' == $type ) {
            return array(
                'text' => config($pfx . 'field_1', '', $i),
                'tag' => config($pfx . 'field_2', '', $i),
                'extend_tag' => '',
            );
        } else if ( 'table' == $type ) {
            return array(
                'table' => config($pfx . 'field_1', '', $i),
            );
        } else if ( 'image' == $type ) {
            return array(
                'caption' => config($pfx . 'field_1', '', $i),
                'path' => config($pfx . 'field_2', '', $i),
                'link' => config($pfx . 'field_3', '', $i),
                'alt' => config($pfx . 'field_4', '', $i),
                'exif' => config($pfx . 'field_6', '', $i),
            );
        } else if ( 'table' == $type ) {
            return array(
                'table' => config($pfx.'field_1', '', $i),
            );
        } else if ( 'file' == $type ) {
            return array(
                'caption'   => config($pfx.'field_1', '', $i),
                'path'      => config($pfx.'field_2', '', $i),
            );
        } else if ( 'osmap' == $type || 'map' == $type ) {
            return array(
                'msg'   => config($pfx.'field_1', '', $i),
                'lat'   => config($pfx.'field_2', '35.185574', $i),
                'lng'   => config($pfx.'field_3', '136.899066', $i),
                'zoom'  => config($pfx.'field_4', '10', $i),
                'view_activate' => '',
                'view_pitch' => '',
                'view_heading' => '',
                'view_zoom' => '',
            );
        } else if ( 'yolp' == $type ) {
            return array(
                'msg'   => config($pfx.'field_1', '', $i),
                'lat'   => config($pfx.'field_2', '35.185574', $i),
                'lng'   => config($pfx.'field_3', '136.899066', $i),
                'zoom'  => config($pfx.'field_4', '10', $i),
                'layer' => config($pfx.'field_5', 'map', $i),
            );
        } else if ( 'youtube' == $type ) {
            return array(
                'youtube_id'    => config($pfx.'field_2', '', $i),
            );
        } else if ( 'video' == $type ) {
            return array(
                'video_id'    => config($pfx.'field_2', '', $i),
            );
        } else if ( 'eximage' == $type ) {
            return array(
                'caption'   => config($pfx.'field_1', '', $i),
                'normal'    => config($pfx.'field_2', '', $i),
                'large'     => config($pfx.'field_3', '', $i),
                'link'      => config($pfx.'field_4', '', $i),
                'alt'       => config($pfx.'field_5', '', $i),
            );
        } else if ( 'quote' == $type ) {
            return array(
                'quote_url' => config($pfx.'field_6', '', $i),
                'html'      => config($pfx.'field_7', '', $i),
                'site_name' => config($pfx.'field_1', '', $i),
                'author'    => config($pfx.'field_2', '', $i),
                'title'     => config($pfx.'field_3', '', $i),
                'description' => config($pfx.'field_4', '', $i),
                'image'     => config($pfx.'field_5', '', $i),
            );
        } else if ( 'media' == $type ) {
            return array(
                'media_id' => config($pfx.'field_1', '', $i),
                'caption' => config($pfx.'field_2', '', $i),
                'alt' => config($pfx.'field_3', '', $i),
                'enlarged' => config($pfx.'field_4', '', $i),
                'use_icon' => config($pfx.'field_5', '', $i),
                'link' => config($pfx.'field_7', '', $i),
            );
        } else if ( 'rich-editor' == $type ) {
            return array(
                'json' => config($pfx.'field_1', '', $i)
            );
        } else if ( 'break' == $type ) {
            return array(
                'label' => config($pfx.'field_1', '', $i),
            );
        } else if ( 'module' == $type ) {
            return array(
                'mid'   => config($pfx.'field_1', '', $i),
                'tpl'   => config($pfx.'field_2', '', $i),
            );
        } else if ( 'custom' == $type ) {
            return array(
                'field' => config($pfx.'field_6', '', $i),
            );
        } else {
            return array();
        }
    }

    /**
     * 編集ページのユニットを組み立て
     *
     * @param array $data
     * @param Acms\Service\View\Engine $Tpl
     * @param array $block
     * @param array $mediaData
     *
     * @return bool
     */
    public function buildAdminColumn($data, $Tpl, $rootBlock = array(), $mediaData = array())
    {
        $rootBlock  = empty($rootBlock) ? array() :
            (is_array($rootBlock) ? $rootBlock : array($rootBlock))
        ;

        $id     = $data['id'];
        $clid   = ite($data, 'clid');
        $typeS  = $data['type'];
        $size   = $data['size'];

        // 特定指定子を除外した、一般名のユニット種別
        $type = detectUnitTypeSpecifier($typeS);

        //------
        // text
        if ( 'text' == $type ) {
            $suffix = '';
            if ( preg_match('@(?:id="([^"]+)"|class="([^"]+)")@', $data['attr'], $match) ) {
                if ( !empty($match[1]) ) $suffix .= '#' . $match[1];
                if ( !empty($match[2]) ) $suffix .= '.' . $match[2];
            }
            foreach ( configArray('column_text_tag') as $i => $tag ) {
                $vars = array(
                    'value' => $tag,
                    'label' => config('column_text_tag_label', '', $i),
                    'extend' => config('column_text_tag_extend_label', '', $i),
                );
                if ( $data['tag'] . $suffix === $tag ) {
                    $vars['selected'] = config('attr_selected');
                }
                $Tpl->add(array_merge(array('textTag:loop', $type), $rootBlock), $vars);
            }
            $textVars = array(
                'id' => $id,
                'extend_tag' => isset($data['extend_tag']) ? $data['extend_tag'] : '',
            );
            buildUnitData($data['text'], $textVars, 'text');
            $Tpl->add(array_merge(array($type), $rootBlock), $textVars);

        //-------
        // table
        } else if ( 'table' == $type ) {
            $vars = array(
                'id' => $id,
            );
            buildUnitData($data['table'], $vars, 'table');
            $Tpl->add(array_merge(array($type), $rootBlock), $vars);
        //-------
        // image
        } else if ( 'image' == $type ) {
            foreach ( configArray('column_image_size_label') as $i => $_label ) {
                $vars  = array(
                    'value'     => config('column_image_size', '', $i),
                    'label'     => config('column_image_size_label', '', $i),
                    'display'   => config('column_image_display_size', '', $i),
                );
                if ( $size == config('column_image_size', '', $i) ) {
                    $vars['selected']  = config('attr_selected');
                }

                $Tpl->add(array_merge(array('size:loop', $type), $rootBlock), $vars);
            }
            if (!isset($data['display_size'])) {
                $data['display_size'] = '';
            }
            $vars  = array(
                'old'       => $data['path'],
                'size_old'  => $size . ':' . $data['display_size'],
                'caption'   => $data['caption'],
                'link'      => $data['link'],
                'alt'       => $data['alt'],
                'exif'      => $data['exif'],
                'id'        => $id,
            );

            buildUnitData($vars['caption'], $vars, 'caption');
            buildUnitData($vars['exif'], $vars, 'exif');
            buildUnitData($vars['link'], $vars, 'link');
            buildUnitData($vars['alt'], $vars, 'alt');
            buildUnitData($data['path'], $vars, 'old');

            if ( isset($data['edit']) ) {
                $edit = $data['edit'];
                $vars['edit:selected#'.$edit] = config('attr_selected');
            }

            //----------------
            // tiny and large
            if ( !empty($data['path']) ) {
                $nXYAry     = array();
                $tXYAry     = array();
                $tinyAry    = array();
                $lXYAry     = array();

                foreach ( explodeUnitData($data['path']) as $normal ) {
                    $nXY   = Storage::getImageSize(ARCHIVES_DIR.$normal);
                    $tiny  = preg_replace('@[^/]+$@', 'tiny-$0', $normal);
                    $large = preg_replace('@[^/]+$@', 'large-$0', $normal);
                    $tXY   = Storage::getImageSize(ARCHIVES_DIR.$tiny);
                    if ( $lXY = Storage::getImageSize(ARCHIVES_DIR.$large) ) {
                        $lXYAry['x'][]  = $lXY[0];
                        $lXYAry['y'][]  = $lXY[1];
                        $largeAry[]     = $large;
                    } else {
                        $lXYAry['x'][]  = '';
                        $lXYAry['y'][]  = '';
                        $largeAry[]     = '';
                    }

                    $nXYAry['x'][] = $nXY[0];
                    $nXYAry['y'][] = $nXY[1];
                    $tXYAry['x'][] = $tXY[0];
                    $tXYAry['y'][] = $tXY[1];

                    $tinyAry[]  = $tiny;
                }

                $popup = otherSizeImagePath($data['path'], 'large');
                if ( !Storage::getImageSize(ARCHIVES_DIR.$popup) ) {
                    $popup = $data['path'];
                }

                $vars   += array(
                    'tiny'  => implodeUnitData($tinyAry),
                    'tinyX' => implodeUnitData($tXYAry['x']),
                    'tinyY' => implodeUnitData($tXYAry['y']),
                    'popup' => $popup,
                    'normalX' => implodeUnitData($nXYAry['x']),
                    'normalY' => implodeUnitData($nXYAry['y']),
                    'largeX' => implodeUnitData($lXYAry['x']),
                    'largeY' => implodeUnitData($lXYAry['y']),
                );

                buildUnitData($vars['tiny'], $vars, 'tiny');
                buildUnitData($vars['tinyX'], $vars, 'tinyX');
                buildUnitData($vars['popup'], $vars, 'popup');
                buildUnitData($vars['normalX'], $vars, 'normalX');
                buildUnitData($vars['normalY'], $vars, 'normalY');
                buildUnitData($vars['largeX'], $vars, 'largeX');
                buildUnitData($vars['largeY'], $vars, 'largeY');

                foreach ( $vars as $key => $val ) {
                    if ( $val == '' ) {
                        unset($vars[$key]);
                    }
                }

            } else {
                $Tpl->add(array_merge(array('preview#none', $type), $rootBlock));
            }

            //------
            // size
//            if ( empty($size) ) {
//                $vars['size:selected#none'] = config('attr_selected');
//            }

            //-------
            // rotate
            if ( function_exists('imagerotate') ) {
                $count = count(explodeUnitData($data['path']));
                for ( $i=0; $i<$count; $i++ ) {
                    if ( empty($i) ) $n = '';
                    else $n = $i + 1;
                    $Tpl->add(array_merge(array('rotate'.$n, $type), $rootBlock));
                }
            }

            //---------------
            // primary image
            if ( array_key_exists('primaryImage', $data) ) {
                $vars['primaryImageId'] = $id;
                if ( !empty($clid) and $data['primaryImage'] == $clid ) {
                    $vars['primaryImageChecked']    = config('attr_checked');
                }
            }

            $Tpl->add(array_merge(array($type), $rootBlock), $vars);

        //------
        // file
        } else if ( 'file' == $type ) {
            $vars  = array(
                'id'        => $id,
            );
            if ( !empty($data['path']) ) {
                $vars['old']      = $data['path'];
                $length = count(explodeUnitData($data['path']));
                buildUnitData($vars['old'], $vars, 'old');

                for ( $i=0; $i<$length; $i++ ) {
                    if ( empty($i) ) $fx = '';
                    else $fx = $i + 1;

                    if ( !isset($vars['old'.$fx]) ) {
                        continue;
                    }
                    $path   = $vars['old'.$fx];
                    $vars['basename'.$fx] = Storage::mbBasename($path);

                    $e    = preg_replace('@.*\.(?=[^.]+$)@', '', $path);
                    $t   = null;
                    if ( in_array($e, configArray('file_extension_document')) ) {
                        $t   = 'document';
                    } else if ( in_array($e, configArray('file_extension_archive')) ) {
                        $t   = 'archive';
                    } else if ( in_array($e, configArray('file_extension_movie')) ) {
                        $t   = 'movie';
                    } else if ( in_array($e, configArray('file_extension_audio')) ) {
                        $t   = 'audio';
                    }
                    $cwd    = getcwd();
                    Storage::changeDir(THEMES_DIR.'system/'.IMAGES_DIR.'fileicon/');
                    $icon   = glob($e.'.*') ? $e : $t;
                    Storage::changeDir($cwd);

                    $vars['icon'.$fx]   = $icon;
                    $vars['type'.$fx]   = $icon;
                }

                $vars['caption']  = $data['caption'];
                $vars['deleteId'] = $id;

                buildUnitData($vars['caption'], $vars, 'caption');
            }
            $Tpl->add(array_merge(array($type), $rootBlock), $vars);

        //-----
        // map
        } else if ( 'map' === $type ) {
            foreach ( configArray('column_map_size_label') as $i => $_label ) {
                $vars  = array(
                    'value'   => config('column_map_size', '', $i),
                    'label'   => config('column_map_size_label', '', $i),
                    'display' => config('column_map_display_size', '', $i),
                );
                if ( $data['size'] == config('column_map_size', '', $i) ) {
                    $vars['selected']  = config('attr_selected');
                }

                $Tpl->add(array_merge(array('size:loop', $type), $rootBlock), $vars);
            }

            $Tpl->add(array_merge(array($type), $rootBlock), array(
                'lat'   => $data['lat'],
                'lng'   => $data['lng'],
                'zoom'  => $data['zoom'],
                'msg'   => $data['msg'],
                'id'    => $id,
                'view_activate' => isset($data['view_activate']) ? $data['view_activate'] : '',
                'view_activate:checked#true' => (isset($data['view_activate']) && $data['view_activate'] === 'true') ? ' checked': '',
                'view_pitch' => isset($data['view_pitch']) ? $data['view_activate'] : '',
                'view_heading' => isset($data['view_heading']) ? $data['view_activate'] : '',
                'view_zoom' => isset($data['view_zoom']) ? $data['view_activate'] : '',
            ));

        } else if ( 'osmap' === $type ) {
            foreach ( configArray('column_map_size_label') as $i => $_label ) {
                $vars  = array(
                    'value'   => config('column_map_size', '', $i),
                    'label'   => config('column_map_size_label', '', $i),
                    'display' => config('column_map_display_size', '', $i),
                );
                if ( $data['size'] == config('column_map_size', '', $i) ) {
                    $vars['selected']  = config('attr_selected');
                }
                $Tpl->add(array_merge(array('size:loop', $type), $rootBlock), $vars);
            }

            $Tpl->add(array_merge(array($type), $rootBlock), array(
                'lat'   => $data['lat'],
                'lng'   => $data['lng'],
                'zoom'  => $data['zoom'],
                'msg'   => $data['msg'],
                'id'    => $id,
            ));

        //-------
        // yolp
        } else if ( 'yolp' == $type ) {
            foreach ( configArray('column_map_size_label') as $i => $_label ) {
                $vars  = array(
                    'value'   => config('column_map_size', '', $i),
                    'label'   => config('column_map_size_label', '', $i),
                    'display' => config('column_map_display_size', '', $i),
                );
                if ( $data['size'] == config('column_map_size', '', $i) ) {
                    $vars['selected']  = config('attr_selected');
                }
                $Tpl->add(array_merge(array('size:loop', $type), $rootBlock), $vars);
            }
            foreach ( configArray('column_map_layer_type') as $j => $layer ) {
                $vars  = array(
                    'value' => $layer,
                    'label' => config('column_map_layer_type_label', '', $j),
                );
                if ( $data['layer'] == $layer ) {
                    $vars['selected']  = config('attr_selected');
                }
                $Tpl->add(array_merge(array('layer:loop', $type), $rootBlock), $vars);
            }
            $Tpl->add(array_merge(array($type), $rootBlock), array(
                'lat'   => $data['lat'],
                'lng'   => $data['lng'],
                'layer' => $data['layer'],
                'zoom'  => $data['zoom'],
                'msg'   => $data['msg'],
                'id'    => $id,
            ));

        //---------
        // youtube
        } else if ( 'youtube' == $type ) {
            foreach ( configArray('column_youtube_size_label') as $i => $_label ) {
                $vars  = array(
                    'value'   => config('column_youtube_size', '', $i),
                    'label'   => config('column_youtube_size_label', '', $i),
                    'display' => config('column_youtube_display_size', '', $i),
                );
                if ( $data['size'] == config('column_youtube_size', '', $i) ) {
                    $vars['selected']  = config('attr_selected');
                }
                $Tpl->add(array_merge(array('size:loop', $type), $rootBlock), $vars);
            }
            $vars = array('id' => $id);
            buildUnitData($data['youtube_id'], $vars, 'youtubeId');

            $Tpl->add(array_merge(array($type), $rootBlock), $vars);

        //---------
        // video
        } else if ( 'video' == $type ) {
            foreach ( configArray('column_video_size_label') as $i => $_label ) {
                $vars  = array(
                    'value'   => config('column_video_size', '', $i),
                    'label'   => config('column_video_size_label', '', $i),
                    'display' => config('column_video_display_size', '', $i),
                );
                if ( $data['size'] == config('column_video_size', '', $i) ) {
                    $vars['selected']  = config('attr_selected');
                }
                $Tpl->add(array_merge(array('size:loop', $type), $rootBlock), $vars);
            }
            $vars = array('id' => $id);
            buildUnitData($data['video_id'], $vars, 'videoId');

            $Tpl->add(array_merge(array($type), $rootBlock), $vars);

        //---------
        // eximage
        } else if ( 'eximage' == $type ) {
            if ( !empty($size) and ($xy = explode('x', $size)) ) {
                $x  = intval($xy[0]);
                $y  = intval(ite($xy, 1));
                $size   = max($x, $y);
            }

            $match  = false;
            foreach ( configArray('column_eximage_size_label') as $i => $_label ) {
                $vars  = array(
                    'value'   => config('column_eximage_size', '', $i),
                    'label'   => config('column_eximage_size_label', '', $i),
                    'display' => config('column_eximage_display_size', '', $i),
                );
                if ( $size == config('column_eximage_size', '', $i) ) {
                    $vars['selected']  = config('attr_selected');
                    $match  = true;
                }
                $Tpl->add(array_merge(array('size:loop', $type), $rootBlock), $vars);
            }
            $vars  = array(
                'caption'   => $data['caption'],
//                'normal'    => $data['normal'],
                'large'     => $data['large'],
                'link'      => $data['link'],
                'alt'       => $data['alt'],
                'id'        => $id,
            );
            if ( !empty($data['normal']) ) $vars['normal']  = $data['normal'];

            if ( !$match ) $vars['size:selected#none'] = config('attr_selected');

            buildUnitData($data['caption'], $vars, 'caption');
            buildUnitData($data['normal'], $vars, 'normal');
            buildUnitData($data['large'], $vars, 'large');
            buildUnitData($data['link'], $vars, 'link');
            buildUnitData($data['alt'], $vars, 'alt');

            $Tpl->add(array_merge(array($type), $rootBlock), $vars);

        //---------
        // quote
        } else if ( 'quote' == $type ) {
            $vars = array(
                'quote_url' => $data['quote_url'],
                'html'      => isset($data['html']) ? $data['html'] : '',
                'site_name' => isset($data['site_name']) ? $data['site_name'] : '',
                'author'    => isset($data['author']) ? $data['author'] : '',
                'title'     => isset($data['title']) ? $data['title'] : '',
                'description'   => isset($data['description']) ? $data['description'] : '',
                'image'     => isset($data['image']) ? $data['image'] : '',
                'id'        => $id,
            );
            buildUnitData($vars['quote_url'], $vars, 'quote_url');
            buildUnitData($vars['html'], $vars, 'html');
            buildUnitData($vars['site_name'], $vars, 'site_name');
            buildUnitData($vars['author'], $vars, 'author');
            buildUnitData($vars['title'], $vars, 'title');
            buildUnitData($vars['description'], $vars, 'description');
            buildUnitData($vars['image'], $vars, 'image');

            $Tpl->add(array_merge(array($type), $rootBlock), $vars);

        //---------
        // media
        } else if ( 'media' == $type ) {
            $DB     = DB::singleton(dsn());

            $midAry = explodeUnitData($data['media_id']);
            $vars   = array('type' => 'image');
            $mediaType = false;
            foreach ( $midAry as $i => $mid ) {
                $mid = intval($mid);
                if ( empty($i) ) $fx = '';
                else $fx = $i + 1;

                if (isset($mediaData[$mid])) {
                    $media = $mediaData[$mid];
                } else {
                    $SQL = SQL::newSelect('media');
                    $SQL->addWhereOpr('media_id', $mid);
                    $media = $DB->query($SQL->get(dsn()), 'row');
                }
                if (empty($media)) {
                    $media = array(
                        'media_type' => '',
                        'media_path' => '',
                        'media_image_size' => '',
                        'media_field_1' => '',
                        'media_field_2' => '',
                        'media_field_3' => '',
                        'media_field_4' => '',
                        'media_file_name' => '',
                        'media_thumbnail' => ''
                    );
                }
                if (isset($media['media_type']) && Media::isImageFile($media['media_type'])) {
                    $mediaType = true;
                } else if (isset($media['media_type']) && Media::isSvgFile($media['media_type'])) {
                    $vars['type'.$fx] = 'svg';
                } else if ($media) {
                    $vars['type'.$fx] = 'file';
                }
                $path = $media['media_path'];
                $ext = ite(pathinfo($path), 'extension');
                $size = $media['media_image_size'];
                $sizes = explode(' x ', $size);
                $landscape = 'true';
                if ($sizes && isset($sizes[0]) && isset($sizes[1])) {
                    $landscape = $sizes[0] > $sizes[1] ? 'true' : 'false';
                }
                $vars += array(
                    'id'            => $id,
                    'media_id'.$fx  => $mid,
                    'caption'.$fx => $media['media_field_1'],
                    'link'.$fx      => $media['media_field_2'],
                    'alt'.$fx       => $media['media_field_3'],
                    'title'.$fx     => $media['media_field_4'],
                    'type'.$fx      => $media['media_type'],
                    'name'.$fx      => $media['media_file_name'],
                    'path'.$fx      => $path,
                    'tiny'.$fx      => otherSizeImagePath($path, 'tiny'),
                    'landscape'.$fx     => $landscape,
                    'media_pdf'.$fx => 'no',
                    'use_icon'.$fx => 'false',
                );
                if ( !empty($ext) ) {
                    $vars['icon'.$fx] = pathIcon($ext);
                }
                if ( !empty($media['media_thumbnail']) ) {
                    $vars['thumbnail'.$fx] = Media::getPdfThumbnail($media['media_thumbnail']);
                    $vars['media_pdf'.$fx] = 'yes';
                    buildUnitData($data['use_icon'], $vars, 'use_icon');
                }
            }

            buildUnitData($data['enlarged'], $vars, 'enlarged');
            buildUnitData($data['link'], $vars, 'override-link');
            buildUnitData($data['caption'], $vars, 'override-caption');
            buildUnitData($data['alt'], $vars, 'override-alt');

            foreach ( configArray('column_media_size_label') as $i => $_label ) {
                $sizeAry  = array(
                    'value'   => config('column_media_size', '', $i),
                    'label'   => config('column_media_size_label', '', $i),
                    'display' => config('column_media_display_size', '', $i),
                );
                if ( $data['size'] == config('column_media_size', '', $i) ) {
                    $sizeAry['selected']  = config('attr_selected');
                }
                $Tpl->add(array_merge(array('size:loop', $type), $rootBlock), $sizeAry);
            }

            //---------------
            // primary image
            if ($mediaType && array_key_exists('primaryImage', $data)) {
                $vars['primaryImageId'] = $id;
                if ( !empty($clid) and $data['primaryImage'] == $clid ) {
                    $vars['primaryImageChecked']    = config('attr_checked');
                }
            }
            $Tpl->add(array_merge(array($type), $rootBlock), $vars);

        } else if ( 'rich-editor' == $type ) {
            $vars = array('id' => $id);
            if (!empty($data['json'])) {
                buildUnitData(RichEditor::render($data['json']), $vars, 'html');
            } else {
                buildUnitData('', $vars, 'html');
            }
            $Tpl->add(array_merge(array($type), $rootBlock), $vars);
        //-------
        // break
        } else if ( 'break' == $type ) {
            $vars = array('id' => $id);
            buildUnitData($data['label'], $vars, 'label');

            $Tpl->add(array_merge(array($type), $rootBlock), $vars);

        //--------
        // module
        } else if ( 'module' == $type ) {
            $mid    = $data['mid'];
            $tpl    = $data['tpl'];
            $vars   = array(
                'mid'   => $mid,
                'tpl'   => $tpl,
                'id'    => $id,
            );
            if ( !empty($mid) ) {
                $module     = loadModule($mid);
                $name       = $module->get('name');
                $identifier = $module->get('identifier');
                $vars['view'] = Tpl::spreadModule($name, $identifier, $tpl);
            }
            $Tpl->add(array_merge(array($type), $rootBlock), $vars);

        //--------
        // custom
        } else if ( 'custom' == $type ) {
            if ( !empty($data['field']) ) {
                $Field  = acmsUnserialize($data['field']);
                if ( !method_exists($Field, 'listFields') ) $Field = null;
            }
            $block      = array_merge(array($typeS), $rootBlock);
            $vars       = array('id' => $id);
            if ( isset($Field) ) {
                $this->injectMediaField($Field, true);
                $this->injectRichEditorField($Field, true);
                $vars += $this->buildField($Field, $Tpl, $block, null, array('id' => $id));
            }
            $Tpl->add($block, $vars);

        } else {
            return false;
        }

        return true;
    }

    /**
     * 編集ページの動的フォームユニットを組み立て
     *
     * @param array $data
     * @param Acms\Service\View\Engine $Tpl
     * @param array $rootBlock
     *
     * @return bool
     */
    public function buildAdminFormColumn($data, $Tpl, $rootBlock = array())
    {
        $rootBlock  = empty($rootBlock) ? array() :
            (is_array($rootBlock) ? $rootBlock : array($rootBlock))
        ;
        $id     = $data['id'];
        $type   = $data['type'];

        //----------------
        // text, textarea
        if ( in_array($type, array('text', 'textarea')) ) {

        //-------------------------
        // radio, select, checkbox
        } else if ( in_array($type, array('radio', 'select', 'checkbox')) ) {
            if ( 1
                && isset($data['values'])
                && $values = acmsUnserialize($data['values'])
            ) {
                if ( is_array($values) ) {
                    foreach ( $values as $val ) {
                        if ( !empty($val) ) {
                            $Tpl->add(array_merge(array($type.'_value:loop'), $rootBlock), array(
                                'value' => $val,
                                'id'    => $id,
                            ));
                        }
                    }
                }
            }
        } else {
            return false;
        }

        $data = array_merge(array(
            'type'              => '',
            'label'             => '',
            'caption'           => '',
            'validator'         => array(),
            'validator-value'   => array(),
            'validator-message' => array(),
        ), $data);

        //---------------
        // label caption
        $Tpl->add(array_merge(array($type), $rootBlock), array(
            'label'             => $data['label'],
            'caption'           => $data['caption'],
            'id'                => $id,
        ));
        //------------
        // validator
        if ( isset($data['validatorSet']) ) {
            $validatorSet   = acmsUnserialize($data['validatorSet']);
            if ( is_array($validatorSet) ) {
                $validator      = $validatorSet['validator'];
                $validator_val  = $validatorSet['validator-value'];
                $validator_mess = $validatorSet['validator-message'];
            } else {
                $validator      = array();
                $validator_val  = array();
                $validator_mess = array();
            }
        } else {
            $validator      = $data['validator'];
            $validator_val  = $data['validator-value'];
            $validator_mess = $data['validator-message'];
        }

        foreach ( $validator as $j => $val ) {
            if ( !empty($val) ) {
                $Tpl->add(array_merge(array('option:loop'), $rootBlock), array(
                    'validator'                 => $val,
                    'validator:selected#'.$val  => config('attr_selected'),
                    'validator-value'           => $validator_val[$j],
                    'validator-message'         => $validator_mess[$j],
                    'id'                        => $id,
                    'unique'                    => 'data-' . ($j + 1),
                ));
            }
        }
        return true;
    }

    /**
     * レイアウトモジュールの1モジュールを組み立て
     *
     * @param string $moduleName
     * @param int $moduleID
     * @param string $moduleTpl
     * @param bool $onlyLayout
     *
     * @return string
     */
    public function spreadModule($moduleName, $moduleID, $moduleTpl, $onlyLayout = false)
    {
        $tpl = 'include/module/template/'.$moduleName.'.html';
        if ( !empty($moduleTpl) ) {
            $tpl = 'include/module/template/'.$moduleName.'/'.$moduleTpl;
        } else {
            $modShort = preg_replace('/'.config('module_identifier_duplicate_suffix').'.*/', '', $moduleID);
            $def = 'include/module/template/'.$moduleName.'/'.$modShort.'.html';
            if ( findTemplate($def) ) {
                $tpl = $def;
            }
        }

        if ( $path = findTemplate($tpl) ) {
            $mTpl   = resolvePath('<!--#include file="'.$tpl.'" vars=""-->', config('theme'), '/');
            if ( $mTpl = spreadTemplate($mTpl, false) ) {
                $opt = ' id="'.$moduleID.'"';

                if ( 1
                    && LAYOUT_EDIT
                    && !LAYOUT_PREVIEW
                    && preg_match('/<!--[\t 　]*BEGIN[\t 　]+layout\#display[^>]*?-->/i', $mTpl)
                ) {
                    \ACMS_GET_Layout::formatBlock($mTpl, 'dummy');
                } else {
                    \ACMS_GET_Layout::formatBlock($mTpl, 'display');

                    if ( $onlyLayout ) {
                        if ( $moduleName === 'Entry_Body' ) {
                            $mTpl   = preg_replace('/<!--[\t 　]*BEGIN_MODULE[\t 　]+Entry_Body[^>]*?-->/', '<!-- BEGIN_MODULE Entry_Body'.$opt.' -->', $mTpl);
                            $mTpl   = build($mTpl, Field_Validation::singleton('post'));
                        } else {
                            $mTpl   = preg_replace(
                                '/<!--[\t 　]*(BEGIN|END)_MODULE+[\t 　]+([^\t 　]+)([^>]*?)[\t 　]*-->/',
                                '', $mTpl);
                            $mTpl   = '<!-- BEGIN_MODULE '.$moduleName.$opt.' -->'.$mTpl.'<!-- END_MODULE '.$moduleName.' -->';
                        }
                    } else if ( $moduleName === 'Entry_Body' ) {
                        $mTpl   = preg_replace('/<!--[\t 　]*BEGIN_MODULE[\t 　]+Entry_Body[^>]*?-->/', '<!-- BEGIN_MODULE Entry_Body'.$opt.' -->', $mTpl);
                        $mTpl   = build($mTpl, Field_Validation::singleton('post'));
                    } else {
                        $mTpl   = preg_replace(
                            '/<!--[\t 　]*(BEGIN|END)_MODULE+[\t 　]+([^\t 　]+)([^>]*?)[\t 　]*-->/',
                            '', $mTpl);
                        $mTpl   = boot($moduleName, $mTpl, $opt, Field_Validation::singleton('post'), Field::singleton('config'));
                    }
                }
                if ( DEBUG_MODE ) {
                    $mTpl = includeCommentBegin($path).$mTpl.includeCommentEnd($path);
                }
                return $mTpl;
            }
        }
        return '';
    }
}
