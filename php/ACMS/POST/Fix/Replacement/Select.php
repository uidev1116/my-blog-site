<?php

class ACMS_POST_Fix_Replacement_Select extends ACMS_POST
{
    var $target;
    var $pattern;
    var $replacement;
    var $updated;

    function post()
    {
        if ( !sessionWithAdministration() ) return false;

        $this->Post->setMethod('checks', 'required');
        
        $Fix = $this->extract('fix');
        $Fix->setMethod('fix_replacement_target', 'required');
        $Fix->setMethod('fix_replacement_pattern', 'required');
        $Fix->setMethod('fix_replacement_replacement', 'required');

        $this->Post->validate(new ACMS_Validator());

        if ( $this->Post->isValidAll() ) {
            $this->target       = $Fix->get('fix_replacement_target');
            $this->pattern      = preg_quote($Fix->get('fix_replacement_pattern'), '@');
            $this->replacement  = $Fix->get('fix_replacement_replacement');
            $this->filter       = $Fix->get('fix_replacement_target_cf_filter');

            foreach ( $this->Post->getArray('checks') as $id ) {
                $this->replace($id);
                Common::deleteFieldCache('eid', $id);
            }

            Cache::flush('temp');

            $this->Post->set('updated', intval($this->updated));
            $this->Post->set('message', 'success');
        }
        return $this->Post;
    }

    function replace($id)
    {
        $DB = DB::singleton(dsn());
        $SQL = null;
        $eid = 0;

        switch ( $this->target ) {
            case 'title':
                $title  = ACMS_RAM::entryTitle($id);
                $title  = preg_replace('@('.$this->pattern.')@iu', $this->replacement, $title);

                $SQL    = SQL::newUpdate('entry');
                $SQL->addUpdate('entry_title', $title);
                $SQL->addWhereOpr('entry_id', $id);
                $eid = $id;
                break;
            case 'unit':
                $unit   = ACMS_RAM::unitField1($id);
                $unit  = preg_replace('@('.$this->pattern.')@iu', $this->replacement, $unit);

                $SQL    = SQL::newUpdate('column');
                $SQL->addUpdate('column_field_1', $unit);
                $SQL->addWhereOpr('column_id', $id);
                $eid = $id;
                break;
            case 'field':
                $ids = preg_split('/:/', $id, 3);
                if ( count($ids) < 3 ) {
                    return false;
                }
                list($eid, $sort, $key) = $ids;

                $SELECT    = SQL::newSelect('field');
                $SELECT->addSelect('field_value');
                $SELECT->addWhereOpr('field_eid', $eid);
                $SELECT->addWhereOpr('field_sort', $sort);
                $SELECT->addWhereOpr('field_key', $key);
                if ( $this->filter ) {
                    $SELECT->addWhereOpr('field_key', $this->filter);
                }
                $field  = $DB->query($SELECT->get(dsn()), 'one');

                if ( empty($field) ) {
                    return false;
                }

                $field  = preg_replace('@('.$this->pattern.')@iu', $this->replacement, $field);

                $SQL    = SQL::newUpdate('field');
                $SQL->addUpdate('field_value', $field);
                $SQL->addWhereOpr('field_eid', $eid);
                $SQL->addWhereOpr('field_sort', $sort);
                $SQL->addWhereOpr('field_key', $key);
                if ( $this->filter ) {
                    $SQL->addWhereOpr('field_key', $this->filter);
                }
                break;
            default:
                return false;
                break;
        }

        $this->updated++;
        $exec = $DB->query($SQL->get(dsn()), 'exec');

        //----------
        // fulltext
        if ($eid) {
            Common::saveFulltext('eid', $eid, Common::loadEntryFulltext($eid));
        }

        return $exec;
    }
}
