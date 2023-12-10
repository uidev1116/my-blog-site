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

        if ($Fix->isValidAll()) {
            $target = $Fix->get('fix_replacement_target');
            $pattern = $Fix->get('fix_replacement_pattern');
            $replacement = $Fix->get('fix_replacement_replacement');
            $includeDescendant = $Fix->get('fix_replacement_target_blog') === 'descendant';

            $this->replace($target, $pattern, $replacement, $includeDescendant);

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

            if (intval($updated) > 0) {
                $targetName = '';
                if ($target === 'title') $targetName = 'タイトル';
                if ($target === 'unit') $targetName = 'ユニット';
                if ($target === 'field') $targetName = 'カスタムフィールド';

                AcmsLogger::info($updated . '件、エントリーの「' . $targetName . '」のテキスト置換を実行しました「' . $pattern . '」->「' . $replacement . '」');
            }
        }
        return $this->Post;
    }

    function replace($target, $pattern, $replacement, $includeDescendant = false)
    {
        $DB = DB::singleton(dsn());
        $SQL = null;
        $blogIds = [BID];

        if ($includeDescendant) {
            $blog = SQL::newSelect('blog');
            $blog->setSelect('blog_id');
            ACMS_Filter::blogTree($blog, BID, 'descendant-or-self');
            $blogIds = DB::query($blog->get(dsn()), 'list');
        }

        switch ( $target ) {
            case 'title':
                $REP = SQL::newFunction('entry_title', array('REPLACE', $pattern, $replacement));
                $SQL = SQL::newUpdate('entry');
                $SQL->addUpdate('entry_title', $REP);
                $SQL->addWhereOpr('entry_title', '%'.$pattern.'%', 'LIKE');
                $SQL->addWhereIn('entry_blog_id', $blogIds);
                break;
            case 'unit':
                $REP = SQL::newFunction('column_field_1', array('REPLACE', $pattern, $replacement));
                $SQL = SQL::newUpdate('column');
                $SQL->addUpdate('column_field_1', $REP);
                $SQL->addWhereOpr('column_field_1', '%'.$pattern.'%', 'LIKE');
                $SQL->addWhereIn('column_blog_id', $blogIds);
                break;
            case 'field':
                $REP = SQL::newFunction('field_value', array('REPLACE', $pattern, $replacement));
                $SQL = SQL::newUpdate('field');
                $SQL->addUpdate('field_value', $REP);
                $SQL->addWhereOpr('field_eid', null, '<>');
                $SQL->addWhereOpr('field_value', '%'.$pattern.'%', 'LIKE');
                $SQL->addWhereIn('field_blog_id', $blogIds);
                break;
        }
        return $DB->query($SQL->get(dsn()), 'exec');
    }
}
