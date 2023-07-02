<?php

class ACMS_POST_Fix_UnitSize extends ACMS_POST_Fix
{
    function post()
    {
        if ( !sessionWithAdministration() ) return false;

        $Fix = $this->extract('fix', new ACMS_Validator());
        $Fix->setMethod('unit_size_unit_type', 'required');
        $Fix->setMethod('unit_size_size_type', 'required');
        $Fix->setMethod('unit_size_target', 'required');
        $Fix->setMethod('unit_size_fix', 'required');

        if ( $this->Post->isValidAll() ) {
            @set_time_limit(0);
            $DB = DB::singleton(dsn());

            $type       = $Fix->get('unit_size_unit_type');
            $sizeType   = $Fix->get('unit_size_size_type');
            $target     = $Fix->get('unit_size_target');
            $criterion  = $Fix->get('unit_size_fix_criterion');
            $value      = $Fix->get('unit_size_fix');

            $SQL    = SQL::newUpdate('column');

            $column = 'column_size';
            if ( $sizeType === 'display' ) {
                switch ( $type ) {
                    case 'youtube':
                    case 'video':
                        $column = 'column_field_3';
                        break;
                    case 'image':
                    case 'osmap':
                    case 'map':
                        $column = 'column_field_5';
                        break;
                    case 'eximage':
                    case 'media':
                        $column = 'column_field_6';
                        break;
                }
            }
            $SQL->addUpdate($column, $criterion.$value);
            $SQL->addWhereOpr($column, $target);
            $SQL->addWhereOpr('column_type', $type);
            $SQL->addWhereOpr('column_blog_id', BID);
            $DB->query($SQL->get(dsn()), 'exec');

            $this->Post->set('message', 'success');
        }

        return $this->Post;
    }
}
