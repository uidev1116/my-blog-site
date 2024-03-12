<?php

class ACMS_POST_Fix_Replacement_Select extends ACMS_POST
{
    /**
     * 対象フィールド
     * @var string
     */
    protected $target;

    /**
     * 置換対象文字
     * @var string
     */
    protected $pattern;

    /**
     * 置換文字
     * @var string
     */
    protected $replacement;

    /**
     * 置換数
     * @var int
     */
    protected $updated;

    /**
     * フィールド名
     * @var string
     */
    protected $filter;

    /**
     * 置換対象ブログID
     * @var array
     */
    protected $blogIds = [];

    function post()
    {
        if (!sessionWithAdministration()) {
            return false;
        }

        $this->Post->setMethod('checks', 'required');

        $Fix = $this->extract('fix');
        $Fix->setMethod('fix_replacement_target', 'required');
        $Fix->setMethod('fix_replacement_pattern', 'required');
        $Fix->setMethod('fix_replacement_replacement', 'required');

        $this->Post->validate(new ACMS_Validator());

        if ($this->Post->isValidAll()) {
            $this->target = $Fix->get('fix_replacement_target');
            $this->pattern = preg_quote($Fix->get('fix_replacement_pattern'), '@');
            $this->replacement = $Fix->get('fix_replacement_replacement');
            $this->filter = $Fix->get('fix_replacement_target_cf_filter');

            if ($Fix->get('fix_replacement_target_blog') === 'descendant') {
                $blog = SQL::newSelect('blog');
                $blog->setSelect('blog_id');
                ACMS_Filter::blogTree($blog, BID, 'descendant-or-self');
                $this->blogIds = DB::query($blog->get(dsn()), 'list');
            } else {
                $this->blogIds = [BID];
            }

            foreach ($this->Post->getArray('checks') as $id) {
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

        switch ($this->target) {
            case 'title':
                $title = ACMS_RAM::entryTitle($id);
                $title = preg_replace('@(' . $this->pattern . ')@iu', $this->replacement, $title);
                $SQL = SQL::newUpdate('entry');
                $SQL->addUpdate('entry_title', $title);
                $SQL->addWhereOpr('entry_id', $id);
                $SQL->addWhereIn('entry_blog_id', $this->blogIds);
                $eid = $id;
                break;
            case 'unit':
                $unit = ACMS_RAM::unitField1($id);
                $unit = preg_replace('@(' . $this->pattern . ')@iu', $this->replacement, $unit);
                $SQL = SQL::newUpdate('column');
                $SQL->addUpdate('column_field_1', $unit);
                $SQL->addWhereOpr('column_id', $id);
                $SQL->addWhereIn('column_blog_id', $this->blogIds);
                $eid = $id;
                break;
            case 'field':
                $ids = preg_split('/:/', $id, 3);
                if (count($ids) < 3) {
                    return false;
                }
                list($eid, $sort, $key) = $ids;

                $SELECT    = SQL::newSelect('field');
                $SELECT->addSelect('field_value');
                $SELECT->addWhereOpr('field_eid', $eid);
                $SELECT->addWhereOpr('field_sort', $sort);
                $SELECT->addWhereOpr('field_key', $key);
                if ($this->filter) {
                    $SELECT->addWhereOpr('field_key', $this->filter);
                }
                $field  = $DB->query($SELECT->get(dsn()), 'one');

                if (empty($field)) {
                    return false;
                }

                $field  = preg_replace('@(' . $this->pattern . ')@iu', $this->replacement, $field);

                $SQL    = SQL::newUpdate('field');
                $SQL->addUpdate('field_value', $field);
                $SQL->addWhereOpr('field_eid', $eid);
                $SQL->addWhereOpr('field_sort', $sort);
                $SQL->addWhereOpr('field_key', $key);
                if ($this->filter) {
                    $SQL->addWhereOpr('field_key', $this->filter);
                }
                $SQL->addWhereIn('field_blog_id', $this->blogIds);
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
