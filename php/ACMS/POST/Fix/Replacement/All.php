<?php

class ACMS_POST_Fix_Replacement_All extends ACMS_POST
{
    function post()
    {
        if ( !sessionWithAdministration() ) return false;
        
        $Fix = $this->extract('fix');
        $Fix->setMethod('fix_replacement_target', 'required');
        $Fix->setMethod('fix_replacement_pattern', 'required');
        $Fix->setMethod('fix_replacement_replacement', 'required');

        $Fix->validate(new ACMS_Validator());

        if ( $Fix->isValidAll() ) {
            $target         = $Fix->get('fix_replacement_target');
            $pattern        = $Fix->get('fix_replacement_pattern');
            $replacement    = $Fix->get('fix_replacement_replacement');

            $this->replace($target, $pattern, $replacement);

            $DB = DB::singleton(dsn());
            $updated = $DB->affected_rows();

            //----------
            // fulltext
            $SQL = SQL::newSelect('entry');
            $SQL->addSelect('entry_id');
            $q = $SQL->get(dsn());
            $DB->query($q, 'fetch');
            while ($row = $DB->fetch($q)) {
                $eid = $row['entry_id'];
                Common::saveFulltext('eid', $eid, Common::loadEntryFulltext($eid));
                Common::deleteFieldCache('eid', $eid);
            }

            Cache::flush('temp');

            $this->Post->set('updated', $updated);
            $this->Post->set('message', 'success');
        }
        return $this->Post;
    }

    function replace($target, $pattern, $replacement)
    {
        $DB     = DB::singleton(dsn());
        $SQL    = null;

        switch ( $target ) {
            case 'title':
                $REP    = SQL::newFunction('entry_title', array('REPLACE', $pattern, $replacement));
                $SQL    = SQL::newUpdate('entry');
                $SQL->addUpdate('entry_title', $REP);
                $SQL->addWhereOpr('entry_title', '%'.$pattern.'%', 'LIKE');
                break;
            case 'unit':
                $REP    = SQL::newFunction('column_field_1', array('REPLACE', $pattern, $replacement));
                $SQL    = SQL::newUpdate('column');
                $SQL->addUpdate('column_field_1', $REP);
                $SQL->addWhereOpr('column_field_1', '%'.$pattern.'%', 'LIKE');
                break;
            case 'field':
                $REP    = SQL::newFunction('field_value', array('REPLACE', $pattern, $replacement));

                $SQL    = SQL::newUpdate('field');
                $SQL->addUpdate('field_value', $REP);
                $SQL->addWhereOpr('field_eid', null, '<>');
                $SQL->addWhereOpr('field_value', '%'.$pattern.'%', 'LIKE');
                break;
        }
        return $DB->query($SQL->get(dsn()), 'exec');
    }
}
