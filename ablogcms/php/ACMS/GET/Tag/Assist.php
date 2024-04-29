<?php

class ACMS_GET_Tag_Assist extends ACMS_GET
{
    function get()
    {
        $Tpl = new Template($this->tpl, new ACMS_Corrector());

        $SQL = SQL::newSelect('tag');
        $SQL->addSelect('tag_name');
        $SQL->addSelect('tag_name', 'tag_amount', null, 'count');
        $SQL->addWhereOpr('tag_blog_id', $this->bid);
        $SQL->addGroup('tag_name');
        $SQL->setLimit(config('tag_assist_limit'));
        ACMS_Filter::tagOrder($SQL, config('tag_assist_order'));
        if (1 < ($tagThreshold = idval(config('tag_assist_threshold')))) {
            $SQL->addHaving('tag_amount >= ' . $tagThreshold);
        }
        $q = $SQL->get(dsn());
        $DB = DB::singleton(dsn());
        if (!$DB->query($q, 'fetch')) {
            return $Tpl->get();
        }

        if (!$row = $DB->fetch($q)) {
            return $Tpl->get();
        }

        $firstLoop = true;
        do {
            if (!$firstLoop) {
                $Tpl->add(['tag:glue', 'tag:loop']);
            }
            $firstLoop = false;
            $Tpl->add('tag:loop', [
                'name' => $row['tag_name'],
                'amount' => $row['tag_amount'],
            ]);
        } while ($row = $DB->fetch($q));

        return $Tpl->get();
    }
}
