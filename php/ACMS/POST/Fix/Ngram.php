<?php

class ACMS_POST_Fix_Ngram extends ACMS_POST
{
    function post()
    {
        if ( !sessionWithAdministration() ) return false;

        @set_time_limit(0);
        $Fix = $this->extract('fix', new ACMS_Validator());
        $Fix->setMethod('ngram', 'required');

        if ( $this->Post->isValidAll() ) {
            $ngram  = $Fix->get('ngram');

            $DB     = DB::singleton(dsn());
            $SQL    = SQL::newSelect('entry');
            $SQL->addLeftJoin('fulltext', 'fulltext_eid', 'entry_id');
            $q  = $SQL->get(dsn());
            $DB->query($q, 'fetch');
            while ( $row = $DB->fetch($q) ) {

                $eid    = intval($row['entry_id']);
                $bid    = intval($row['entry_blog_id']);

                $SQL    = SQL::newSelect('column');
                $SQL->addWhereOpr('column_entry_id', $eid);
                $_all   = $DB->query($SQL->get(dsn()), 'all');

                $text   = '';
                $meta   = '';
                foreach ( $_all as $_row ) {
                    if ( 'text' == $_row['column_type'] ) {
                        $_text  = $_row['column_field_1'];
                        if ( 'markdown' == $_row['column_field_2'] ) {
                            $_text = Common::parseMarkdown($_text);
                        }
                        $text   .= $_text.' ';
                    } else {
                        $meta   .= $_row['column_field_1'].' ';
                    }
                }
                $meta   .= $row['entry_title'].' ';

                //--------------------------------
                // field expect fix markdown, tag
                $SQL    = SQL::newSelect('field');
                $SQL->setSelect('field_value');
                $SQL->addWhereOpr('field_search', 'on');
                $SQL->addWhereOpr('field_eid', $eid);
                $SQL->addWhereOpr('field_blog_id', $bid);
                $fQ = $SQL->get(dsn());
                if ( $DB->query($fQ, 'fetch') ) { while ( $fRow = $DB->fetch($fQ) ) {
                    $meta   .= $fRow['field_value'].' ';
                } }

                $SQL    = SQL::newDelete('fulltext');
                $SQL->addWhereOpr('fulltext_eid', $eid);
                $SQL->addWhereOpr('fulltext_blog_id', $bid);
                $DB->query($SQL->get(dsn()), 'exec');

                $SQL    = SQL::newInsert('fulltext');
                $SQL->addInsert('fulltext_value', 
                    preg_replace('@\s+@', ' ', strip_tags($text)).
                    "\r\n\r\n".preg_replace('@\s+@', ' ', strip_tags($meta))
                );
                if ( $ngram ) {
                    $SQL->addInsert('fulltext_ngram', 
                        preg_replace('@(　|\s)+@', ' ', join(' ', ngram(strip_tags($text.' '.$meta), $ngram)))
                    );
                }
                $SQL->addInsert('fulltext_eid', $eid);
                $SQL->addInsert('fulltext_blog_id', $bid);
                $DB->query($SQL->get(dsn()), 'exec');
            }
            $this->Post->set('message', 'success');
        }
        return $this->Post;
    }
}
