<?php

class ACMS_GET_Admin_Entry_Autocomplete extends ACMS_GET_Entry_Summary
{
    public $_axis = [
        'bid'   => 'descendant-or-self',
        'cid'   => 'descendant-or-self',
    ];

    public $_scope = [
        'keyword'   => 'global',
    ];

    function initVars()
    {
        return [
            'orderFieldName'   => '',
            'order'            => 'datetime-desc',
            'limit'            => 20,
            'offset'           => 0,
            'indexing'         => 'on',
            'subCategory'      => 'on',
            'secret'           => 'off',
            'newtime'          => 'off',
            'unit'             => 1,
            'notfound'         => 'off',
            'notfoundStatus404' => 'off',
            'noimage'          => 'on',
            'imageX'           => 0,
            'imageY'           => 0,
            'imageTrim'        => 'off',
            'imageZoom'        => 'off',
            'imageCenter'      => 'off',
            'pagerDelta'       => 3,
            'pagerCurAttr'     => ' class="cur"',
            'hiddenCurrentEntry'    => 'off',
            'loop_class'            => '',
            'categoryInfoOn'    => 'on',
            'categoryFieldOn'   => 'off',
        ];
    }

    function get()
    {
        if (!$this->setConfig()) {
            return '';
        }

        $DB = DB::singleton(dsn());
        $Tpl = new Template($this->tpl, new ACMS_Corrector());

        $q = $this->buildQuery();
        $this->entries = $DB->query($q, 'all');
        $this->buildEntries($Tpl);

        $json = preg_replace(
            '/[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]' .
            '|[\x00-\x7F][\x80-\xBF]+' .
            '|([\xC0\xC1]|[\xF0-\xFF])[\x80-\xBF]*' .
            '|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})' .
            '|[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})/S',
            '?',
            $Tpl->get()
        );
        $json = buildIF($json);

        header('Content-Type: application/json; charset=utf-8');
        echo($json);
        die();
    }

    /**
     * sqlの組み立て
     *
     * @return SQL_Select
     */
    function buildQuery()
    {
        $SQL = SQL::newSelect('entry');

        $SQL->addLeftJoin('blog', 'blog_id', 'entry_blog_id');
        $SQL->addLeftJoin('category', 'category_id', 'entry_category_id');

        $this->filterQuery($SQL);
        $this->limitQuery($SQL);
        $this->orderQuery($SQL);

        return $SQL->get(dsn());
    }

    /**
     * ブログの絞り込み
     *
     * @param SQL_Select & $SQL
     * @param bool $multi
     * @return void
     */
    function blogFilterQuery(&$SQL, $multi)
    {
        if (!empty($this->bid) && is_int($this->bid) && $this->blogAxis() === 'self') {
            $SQL->addWhereOpr('entry_blog_id', $this->bid);
        } elseif (!empty($this->bid)) {
            $this->blogSubQuery = SQL::newSelect('blog');
            $this->blogSubQuery->setSelect('blog_id');
            if (is_int($this->bid)) {
                if ($multi) {
                    ACMS_Filter::blogTree($this->blogSubQuery, $this->bid, 'descendant-or-self');
                } else {
                    ACMS_Filter::blogTree($this->blogSubQuery, $this->bid, $this->blogAxis());
                }
            } else {
                if (strpos($this->bid, ',') !== false) {
                    $this->blogSubQuery->addWhereIn('blog_id', explode(',', $this->bid));
                }
            }
        }
    }
}
