<?php

class ACMS_POST_Fix_BlogDelete extends ACMS_POST
{
    function post()
    {
        $this->Post->reset(true);

        do {
            $selected   = intval($this->Post->get('i'));
            $count      = count($this->Post->getArray('count'));
            if ( 0
                or 0 !== ACMS_RAM::blogParent(BID)
                or !sessionWithAdministration()
                or 1 > $selected
                or $count < $selected
            ) {
                $this->Post->setMethod('delete', 'operable', false);
                break;
            }

            $DB     = DB::singleton(dsn());
            for ( $i=1; $i<=$count; $i++ ) {
                if ( $selected === $i ) continue;

                $SQL    = SQL::newSelect('blog');
                $SQL->setSelect('blog_id', 'blog_amount', null, 'count');
                $SQL->addWhereOpr('blog_id', intval($this->Post->get('bid_'.$i)));
                $SQL->addWhereOpr('blog_code', strval($this->Post->get('code_'.$i)));
                $SQL->addWhereOpr('blog_status', strval($this->Post->get('status_'.$i)));
                $SQL->addWhereOpr('blog_parent', intval($this->Post->get('parent_'.$i)));
                $SQL->addWhereOpr('blog_sort', intval($this->Post->get('sort_'.$i)));
                $SQL->addWhereOpr('blog_left', intval($this->Post->get('left_'.$i)));
                $SQL->addWhereOpr('blog_right', intval($this->Post->get('right_'.$i)));
                $SQL->addWhereOpr('blog_name', strval($this->Post->get('name_'.$i)));
                $SQL->addWhereOpr('blog_domain', strval($this->Post->get('domain_'.$i)));
                $SQL->addWhereOpr('blog_indexing', strval($this->Post->get('indexing_'.$i)));
                $SQL->setGroup('blog_id');

                if ( 2 <= $DB->query($SQL->get(dsn()), 'one') ) {
                    $this->Post->setMethod('delete', 'double', false);
                    break 2;
                }

                $SQL    = SQL::newDelete('blog');
                $SQL->addWhereOpr('blog_id', intval($this->Post->get('bid_'.$i)));
                $SQL->addWhereOpr('blog_code', strval($this->Post->get('code_'.$i)));
                $SQL->addWhereOpr('blog_status', strval($this->Post->get('status_'.$i)));
                $SQL->addWhereOpr('blog_parent', intval($this->Post->get('parent_'.$i)));
                $SQL->addWhereOpr('blog_sort', intval($this->Post->get('sort_'.$i)));
                $SQL->addWhereOpr('blog_left', intval($this->Post->get('left_'.$i)));
                $SQL->addWhereOpr('blog_right', intval($this->Post->get('right_'.$i)));
                $SQL->addWhereOpr('blog_name', strval($this->Post->get('name_'.$i)));
                $SQL->addWhereOpr('blog_domain', strval($this->Post->get('domain_'.$i)));
                $SQL->addWhereOpr('blog_indexing', strval($this->Post->get('indexing_'.$i)));
                $DB->query($SQL->get(dsn()), 'exec');
                ACMS_RAM::blog(intval($this->Post->get('bid_'.$i)), null);
            }

            $this->Post->set('delete', 'success');
        } while ( false );

        $this->Post->validate();
        return $this->Post;

    }
}
