<?php

class ACMS_POST_Rule_Index_Sort extends ACMS_POST_Rule
{
    protected $fromList;

    public function post()
    {
        $this->validate();

        if ( $this->Post->isValidAll() ) {
            $this->init();
            $this->sort();
            $this->trim();
        }

        return $this->Post;
    }
    
    protected function validate()
    {
        $this->Post->setMethod('checks', 'required');
        $this->Post->setMethod('rule', 'operable', sessionWithAdministration());
        $this->Post->validate(new ACMS_Validator());
    }

    protected function init()
    {
        $DB = DB::singleton(dsn());

        $SQL = SQL::newSelect('rule');
        $SQL->addSelect('rule_id');
        $SQL->addWhereOpr('rule_blog_id', BID);

        foreach ( $DB->query($SQL->get(dsn()), 'all') as $rule ) {
            $rid = $rule['rule_id'];
            if ( !($sort = intval($this->Post->get('sort-current'.$rid))) ) { continue; }
            $this->fromList[$rid] = $sort;

            $SQL = SQL::newUpdate('rule');
            $SQL->addUpdate('rule_sort', $sort);
            $SQL->addWhereOpr('rule_id', $rid);
            $SQL->addWhereOpr('rule_blog_id', BID);
            $DB->query($SQL->get(dsn()), 'exec');

            ACMS_RAM::rule($rid, null);
        }
    }

    protected function sort()
    {
        foreach ( $this->Post->getArray('checks') as $rid ) {
            if ( !isset($this->fromList[$rid]) ) { continue; }

            $from = $this->fromList[$rid];
            $to = $this->Post->get('sort-'.$rid);

            if ( !$from ) { continue; }
            if ( !$to ) { continue; }
            if ( $from == $to ) { continue; }

            $this->move($to, $rid);
        }
    }

    protected function move($to, $rid)
    {
        $DB = DB::singleton(dsn());

        $SQL = SQL::newUpdate('rule');
        $SQL->setUpdate('rule_sort', $to);
        $SQL->addWhereOpr('rule_id', $rid);
        $SQL->addWhereOpr('rule_blog_id', BID);
        $DB->query($SQL->get(dsn()), 'exec');

        ACMS_RAM::rule($rid, null);
    }
}
