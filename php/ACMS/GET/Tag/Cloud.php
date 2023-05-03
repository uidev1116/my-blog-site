<?php

class ACMS_GET_Tag_Cloud extends ACMS_GET
{
    var $_axis  = array(
        'bid'   => 'self',
        'cid'   => 'self'
    );

    function get()
    {
        $DB     = DB::singleton(dsn());

        $SQL    = SQL::newSelect('tag');
        $SQL->addSelect('tag_name');
        $SQL->addSelect('tag_name', 'tag_amount', null, 'count');

        $SQL->addLeftJoin('blog', 'blog_id', 'tag_blog_id');

        $EntrySub = SQL::newSelect('entry');
        $EntrySub->setSelect('entry_id');
        ACMS_Filter::entrySession($EntrySub);
        ACMS_Filter::entrySpan($EntrySub, $this->start, $this->end);
        if ( !empty($this->Field) ) { ACMS_Filter::entryField($EntrySub, $this->Field); }
        if ( !empty($this->eid) ) { $EntrySub->addWhereOpr('entry_id', $this->eid); }
        if ($this->cid) {
            $EntrySub->addLeftJoin('category', 'entry_category_id', 'category_id');
            ACMS_Filter::categoryTree($EntrySub, $this->cid, $this->categoryAxis());
        }

        $BlogSub    = SQL::newSelect('blog');
        $BlogSub->setSelect('blog_id');
        ACMS_Filter::blogTree($BlogSub, $this->bid, $this->blogAxis());
        ACMS_Filter::blogStatus($EntrySub);

        $SQL->addWhereIn('tag_entry_id', $EntrySub);
        $SQL->addWhereIn('tag_blog_id', $DB->subQuery($BlogSub));

        $SQL->addGroup('tag_name');
        if ( 1 < ($tagThreshold = idval(config('tag_cloud_threshold'))) ) {
            $SQL->addHaving('tag_amount >= '.$tagThreshold);
        }
        $SQL->setLimit(config('tag_cloud_limit'));
        ACMS_Filter::tagOrder($SQL, config('tag_cloud_order'));
        $q  = $SQL->get(dsn());

        $all    = $DB->query($q, 'all');
        if ( !$cnt = count($all) ) {
            return false;
        }

        $tags       = array();
        $amounts    = array();
        foreach ( $all as $row ) {
            $tag        = $row['tag_name'];
            $amount     = $row['tag_amount'];
            $tags[$tag] = $amount;
            $amounts[]  = $amount;
        }
        $min = empty($amount) ? 0 : min($amounts);
        $max = empty($amount) ? 0 : max($amounts);

        $c  = ($max <> $min) ? (24 / (sqrt($max) - sqrt($min))) : 1;
        $x  = ceil(sqrt($min) * $c);

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $this->buildModuleField($Tpl);

        $i = 0;
        $categoryContext = config('tag_cloud_link_category_context') === 'on';
        foreach ( $tags as $tag => $amount ) {
            if ( !empty($i) ) $Tpl->add('glue');
            $context = array(
                'bid'   => $this->bid,
                'tag'   => $tag,
            );
            if ($categoryContext && $this->cid) {
                $context['cid'] = $this->cid;
            }
            $Tpl->add('tag:loop', array(
                'level'     => ceil(sqrt($amount) * $c) - $x + 1,
                'url'       => acmsLink($context),
                'path'      => acmsPath($context),
                'amount'    => $amount,
                'name'      => $tag,
            ));
            $i++;
        }

        return $Tpl->get();
    }
}
