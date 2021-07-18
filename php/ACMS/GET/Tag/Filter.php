<?php

class ACMS_GET_Tag_Filter extends ACMS_GET
{
    var $_scope = array(
        'tag'   => 'global',
    );

    function get()
    {
        if ( !$cnt = count($this->tags) ) { return false; }
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $this->buildModuleField($Tpl);
        $categoryContext = config('tag_cloud_link_category_context') === 'on';

        if ( $cnt > config('tag_filter_selected_limit') ) {
            $cnt    = config('tag_filter_selected_limit');
        }

        $stack  = array();
        for ( $i=0; $i<$cnt;$i++ ) {
            $stack[] = $this->tags[$i];
        }

        $tags   = array();
        for ( $i=0; $i<$cnt; ) {
            $tag    = $this->tags[$i];
            $tags[] = $tag;
            if ( $cnt <> ++$i ) { $Tpl->add('glue'); }

            // 現在選択中のタグの中から該当の$tagを除いたものを表示
            $rejects = $stack;
            unset($rejects[array_search($tag, $tags)]);

            $context = array(
                'bid' => $this->bid,
                'tag' => array($tag),
            );
            $context2 = array(
                'bid' => $this->bid,
                'tag' => array_merge($rejects), // indexを振り直し（unsetで空いた分）
            );
            if ($categoryContext && $this->cid) {
                $context['cid'] = $this->cid;
                $context2['cid'] = $this->cid;
            }

            $vars = array(
                'name'  => $tag,
                'url'   => acmsLink($context),
                'path'  => acmsPath($context),
                'omitUrl'=> acmsLink($context2),
            );
            $Tpl->add('selected:loop', $vars);
        }

        $SQL    = SQL::newSelect('tag', 'tag0');
        $SQL->addSelect('tag_name', null, 'tag0');
        $SQL->addSelect('tag_name', 'tag_amount', 'tag0', 'count');
        $SQL->addWhereOpr('tag_blog_id', $this->bid, '=', 'AND', 'tag0');
        foreach ( $this->tags as $i => $tag ) {
            $SQL->addLeftJoin('tag', 'tag_entry_id', 'tag_entry_id', 'tag'.($i+1), 'tag'.$i);
            $SQL->addWhereOpr('tag_name', $tag, '=', 'AND', 'tag'.($i+1));
        }
        foreach ( $this->tags as $tag ) {
            $SQL->addWhereOpr('tag_name', $tag, '<>', 'AND', 'tag0'/*.$i*/);
        }
        $SQL->addLeftJoin('entry', 'entry_id', 'tag_entry_id', null, 'tag0');
        ACMS_Filter::entrySession($SQL);
        if ( !empty($this->Field) ) { ACMS_Filter::entryField($SQL, $this->Field); }
        ACMS_Filter::tagOrder($SQL, config('tag_filter_order'));
        $SQL->addGroup('tag_name', 'tag0');
        if (1 < ($tagThreshold = intval(config('tag_filter_threshold')))) {
            $SQL->addHaving('tag_amount >= '.$tagThreshold);
        }
        if ($this->cid) {
            $SQL->addLeftJoin('category', 'entry_category_id', 'category_id');
            ACMS_Filter::categoryTree($SQL, $this->cid, $this->categoryAxis());
        }
        $SQL->setLimit(config('tag_filter_limit'));
        $q  = $SQL->get(dsn());

        $DB = DB::singleton(dsn());
        $all = $DB->query($q, 'all');

        if ( 0
            or config('tag_filter_selected_limit') <= $cnt
            or !$cnt = count($all)
        ) {
            return $Tpl->get();
        }

        $i = 0;
        while ( $row = array_shift($all) ) {
            $tag    = $row['tag_name'];
            $tags   = $this->tags;
            $tags[] = $tag;
            if ( $cnt <> ++$i ) { $Tpl->add(array('glue', 'choice:loop')); }
            $context = array(
                'bid'   => $this->bid,
                'tag'   => $tags,
            );
            if ($categoryContext && $this->cid) {
                $context['cid'] = $this->cid;
            }
            $Tpl->add('choice:loop', array(
                'name'  => $row['tag_name'],
                'url'   => acmsLink($context),
                'path'  => acmsPath($context),
            ));
        }

        return $Tpl->get();
    }
}

