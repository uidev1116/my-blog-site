<?php

class ACMS_POST_Rule_Index_Sort extends ACMS_POST_Rule
{
    protected $fromList;

    public function post()
    {
        $this->validate();

        if ($this->Post->isValidAll()) {
            $this->init();
            $this->sort();
            $this->trim();

            $sql = SQL::newSelect('rule');
            $sql->addWhereOpr('rule_blog_id', BID);
            $sql->setOrder('rule_sort', 'ASC');
            $rules = DB::query($sql->get(dsn()), 'all');

            $result = [];
            foreach ($rules as $rule) {
                $result[] = [
                    'rid' => $rule['rule_id'],
                    'name' => $rule['rule_name'],
                    'sort' => $rule['rule_sort'],
                ];
            }
            AcmsLogger::info('選択したルールの優先度を変更しました', $result);
        } else {
            if (!$this->Post->isValid('checks', 'required')) {
                AcmsLogger::info('ルールが選択されていないため、ルールの優先度を変更できませんでした');
            }
            if (!$this->Post->isValid('rule', 'operable')) {
                AcmsLogger::info('権限がないため、選択したルールの優先度を変更できませんでした');
            }
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
