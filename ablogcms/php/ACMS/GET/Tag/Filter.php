<?php

class ACMS_GET_Tag_Filter extends ACMS_GET_Tag_Cloud
{
    public $_scope = array(
        'tag' => 'global',
    );

    function get()
    {
        $cnt = count($this->tags);
        if ($cnt === 0) {
            return false;
        }
        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        $DB = DB::singleton(dsn());
        $this->buildModuleField($Tpl);
        $context = $this->getBaseUrlContext(
            config('tag_filter_url_context', false),
            config('tag_filter_link_category_context') === 'on'
        );
        $context2 = $context;
        if ($cnt > config('tag_filter_selected_limit')) {
            $cnt = config('tag_filter_selected_limit');
        }
        $stack = [];
        for ($i = 0; $i < $cnt; $i++) {
            $stack[] = $this->tags[$i];
        }
        $tags = [];
        for ($i = 0; $i < $cnt;) {
            $tag = $this->tags[$i];
            $tags[] = $tag;
            if ($cnt <> ++$i) {
                $Tpl->add('glue');
            }
            // 現在選択中のタグの中から該当の$tagを除いたものを表示
            $rejects = $stack;
            unset($rejects[array_search($tag, $tags)]);

            $context['tag'] = [$tag];
            $context2['tag'] = array_merge($rejects); // indexを振り直し（unsetで空いた分）
            $vars = [
                'name' => $tag,
                'url' => acmsLink($context, false),
                'path' => acmsPath($context),
                'omitUrl' => acmsLink($context2, false),
            ];
            $Tpl->add('selected:loop', $vars);
        }

        $SQL = SQL::newSelect('tag', 'tag0');
        $SQL->addSelect('tag_name', null, 'tag0');
        $SQL->addSelect('tag_name', 'tag_amount', 'tag0', 'count');
        foreach ($this->tags as $i => $tag) {
            $SQL->addLeftJoin('tag', 'tag_entry_id', 'tag_entry_id', 'tag' . ($i + 1), 'tag' . $i);
            $SQL->addWhereOpr('tag_name', $tag, '=', 'AND', 'tag' . ($i + 1));
        }
        foreach ($this->tags as $tag) {
            $SQL->addWhereOpr('tag_name', $tag, '<>', 'AND', 'tag0');
        }

        $multiId = false;
        $EntrySub = SQL::newSelect('entry');
        $EntrySub->setSelect('entry_id');
        $EntrySub->addLeftJoin('category', 'entry_category_id', 'category_id');
        ACMS_Filter::entrySession($EntrySub);
        if (!empty($this->Field)) {
            ACMS_Filter::entryField($EntrySub, $this->Field);
        }

        $CategorySub = null;
        if (!empty($this->cid)) {
            $CategorySub = SQL::newSelect('category');
            $CategorySub->setSelect('category_id');
            if (is_int($this->cid)) {
                ACMS_Filter::categoryTree($CategorySub, $this->cid, $this->categoryAxis());
            } elseif (strpos($this->cid, ',') !== false) {
                $CategorySub->addWhereIn('category_id', explode(',', $this->cid));
                $multiId = true;
            }
            ACMS_Filter::categoryStatus($CategorySub);
        } else {
            ACMS_Filter::categoryStatus($EntrySub);
        }
        if ($CategorySub) {
            $EntrySub->addWhereIn('entry_category_id', $DB->subQuery($CategorySub));
        }

        $BlogSub = SQL::newSelect('blog');
        $BlogSub->setSelect('blog_id');
        if (is_int($this->bid)) {
            if ($multiId) {
                ACMS_Filter::blogTree($BlogSub, $this->bid, 'descendant-or-self');
            } else {
                ACMS_Filter::blogTree($BlogSub, $this->bid, $this->blogAxis());
            }
        } elseif (strpos($this->bid, ',') !== false) {
            $BlogSub->addWhereIn('blog_id', explode(',', $this->bid));
        }
        ACMS_Filter::blogStatus($BlogSub);

        $SQL->addWhereIn('tag_entry_id', $DB->subQuery($EntrySub), 'AND', 'tag0');
        $SQL->addWhereIn('tag_blog_id', $DB->subQuery($BlogSub), 'AND', 'tag0');

        ACMS_Filter::tagOrder($SQL, config('tag_filter_order'));
        $SQL->addGroup('tag_name', 'tag0');
        if (1 < ($tagThreshold = intval(config('tag_filter_threshold')))) {
            $SQL->addHaving('tag_amount >= ' . $tagThreshold);
        }
        $SQL->setLimit(config('tag_filter_limit'));
        $q = $SQL->get(dsn());
        $all = $DB->query($q, 'all');

        if (
            config('tag_filter_selected_limit') <= $cnt
            || !$cnt = count($all)
        ) {
            return $Tpl->get();
        }

        $i = 0;
        while ($row = array_shift($all)) {
            $tag = $row['tag_name'];
            $tags = $this->tags;
            $tags[] = $tag;
            if ($cnt <> ++$i) {
                $Tpl->add(array('glue', 'choice:loop'));
            }
            $context['tag'] = $tags;
            $Tpl->add('choice:loop', [
                'name' => $row['tag_name'],
                'url' => acmsLink($context),
                'path' => acmsPath($context),
            ]);
        }

        return $Tpl->get();
    }
}
