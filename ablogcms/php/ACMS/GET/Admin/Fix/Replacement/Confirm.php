<?php

class ACMS_GET_Admin_Fix_Replacement_Confirm extends ACMS_GET_Admin_Fix
{
    private $limit;

    function select_title($word)
    {
        $DB = DB::singleton(dsn());

        if ( empty($word) ) {
            return array();
        }
        $SQL = SQL::newSelect('entry');
        $SQL->addSelect('entry_id');
        $SQL->addSelect('entry_title');
        $SQL->addWhereOpr('entry_title', '%'.$word.'%', 'LIKE');

        $all    = $DB->query($SQL->get(dsn()), 'all');
        $list   = array();
        foreach ( $all as $row ) {
            $list[] = array(
                'id'    => $row['entry_id'],
                'text'  => $row['entry_title'],
                'eid'   => $row['entry_id'],
            );
        }
        return $list;
    }

    function select_text_unit($word)
    {
        $DB = DB::singleton(dsn());

        if ( empty($word) ) {
            return array();
        }
        $SQL = SQL::newSelect('column');
        $SQL->addWhereOpr('column_field_1', '%'.$word.'%', 'LIKE');

        $all    = $DB->query($SQL->get(dsn()), 'all');
        $list   = array();
        foreach ( $all as $row ) {
            $list[] = array(
                'id'    => $row['column_id'],
                'text'  => $row['column_field_1'],
                'eid'   => $row['column_entry_id'],
            );
        }
        return $list;
    }

    function select_customfield($word, $filter)
    {
        $DB = DB::singleton(dsn());

        if ( empty($word) ) {
            return array();
        }
        $SQL = SQL::newSelect('field');
        $SQL->addSelect('field_key');
        $SQL->addSelect('field_sort');
        $SQL->addSelect('field_eid');
        $SQL->addSelect('field_value');
        $SQL->addWhereOpr('field_eid', null, '<>');
        $SQL->addWhereOpr('field_value', '%'.$word.'%', 'LIKE');
        if ( !empty($filter) ) {
            $SQL->addWhereOpr('field_key', $filter);
        }

        $all    = $DB->query($SQL->get(dsn()), 'all');
        $list   = array();
        foreach ( $all as $row ) {
            $list[] = array(
                'id'    => $row['field_eid'].':'.$row['field_sort'].':'.$row['field_key'],
                'text'  => $row['field_value'],
                'eid'   => $row['field_eid'],
                'key'   => $row['field_key'],
            );
        }
        return $list;
    }

    function get()
    {
        if ( !sessionWithAdministration() ) return false;

        @set_time_limit(0);

        $Tpl            = new Template($this->tpl, new ACMS_Corrector());
        $step           = $this->Post->get('step');

        $Fix            = $this->Post->getChild('fix');
        $target         = $Fix->get('fix_replacement_target');
        $pattern        = $Fix->get('fix_replacement_pattern');
        $filter         = $Fix->get('fix_replacement_target_cf_filter');
        $this->limit    = $Fix->get('fix_replacement_limit', 100);

        if ( $step !== 'confirm' ) {
            return false;
        }

        $list = array();

        switch ( $target ) {
            case 'title':
                $list   = $this->select_title($pattern);
                break;
            case 'unit':
                $list   = $this->select_text_unit($pattern);
                break;
            case 'field':
                $list   = $this->select_customfield($pattern, $filter);
                $Tpl->add('field_name');
                break;
            default:
                return false;
                break;
        }

        if ( empty($list) ) {
            $Tpl->add('notFound');

            return $Tpl->get();
        }

        foreach ( $list as $row ) {
            $id     = $row['id'];
            $eid    = $row['eid'];
            $hits   = $row['text'];
            $hits   = preg_replace('/(' . preg_quote($pattern, '/') . ')/iu' ,'<strong class="highlight1">$1</strong>', $hits);

            $loop = array(
                'id'    => $id,
                'text'  => $hits,
                'url'   => acmsLink(array(
                    'eid' => $eid
                )),
            );
            if ( isset($row['key']) ) {
                $loop['key'] = $row['key'];
            }
            $Tpl->add('found:loop', $loop);
        }

        return $Tpl->get();
    }
}
