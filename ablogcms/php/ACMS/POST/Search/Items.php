<?php

class ACMS_POST_Search_Items extends ACMS_POST
{
    public $isCacheDelete = false;

    /**
     * @var array
     */
    public $_scope = array(
        'keyword' => 'global',
    );

    /**
     * @var int
     */
    protected $limit = 10;

    /**
     * @var string
     */
    protected $keyword = '';

    /**
     *
     */
    function post()
    {
        $json = array();
        $this->keyword = $this->Post->get('word');

        if (
            1
            && !empty($this->keyword)
            && !!SUID
        ) {
            $json[] = array(
                'title' => gettext('ブログ'),
                'enTitle' => 'Blogs',
                'items' => $this->blogJSON(),
            );
            $json[] = array(
                'title' => gettext('エントリー'),
                'enTitle' => 'Entries',
                'items' => $this->entryJSON(),
            );
            $json[] = array(
                'title' => gettext('カテゴリー'),
                'enTitle' => 'Categories',
                'items' => $this->categoryJSON(),
            );
            $json[] = array(
                'title' => gettext('モジュール'),
                'enTitle' => 'Modules',
                'items' => $this->moduleJSON(),
            );
        }
        Common::responseJson($json);
    }

    /**
     * @return array
     */
    protected function blogJSON()
    {
        $json = array();

        $SQL = SQL::newSelect('blog');
        ACMS_Filter::blogTree($SQL, SBID, 'descendant-or-self');
        ACMS_Filter::blogKeyword($SQL, $this->keyword);
        $SQL->setLimit($this->limit);
        $all = DB::query($SQL->get(dsn()), 'all');

        foreach ($all as $item) {
            $bid = $item['blog_id'];
            $json[] = array(
                'bid' => $bid,
                'blogName' => ACMS_RAM::blogName($bid),
                'title' => $item['blog_name'],
                'subtitle' => $item['blog_code'],
                'url' => acmsLink(array(
                    'bid' => $bid,
                    'admin' => 'top',
                )),
            );
        }
        return $json;
    }

    /**
     * @return array
     */
    protected function entryJSON()
    {
        $json = array();

        $SQL = SQL::newSelect('entry');
        $SQL->addLeftJoin('blog', 'blog_id', 'entry_blog_id');
        ACMS_Filter::entrySession($SQL);
        ACMS_Filter::blogTree($SQL, SBID, 'descendant-or-self');
        ACMS_Filter::entryKeyword($SQL, $this->keyword);
        $SQL->setLimit($this->limit);
        $all = DB::query($SQL->get(dsn()), 'all');

        foreach ($all as $item) {
            $id = $item['entry_id'];
            $bid = $item['entry_blog_id'];
            $json[] = array(
                'id' => $id,
                'bid' => $bid,
                'blogName' => ACMS_RAM::blogName($bid),
                'title' => $item['entry_title'],
                'subtitle' => $item['entry_code'],
                'url' => acmsLink(array(
                    'eid' => $id,
                    'bid' => $bid,
                    'admin' => 'entry_editor',
                )),
            );
        }
        return $json;
    }

    /**
     * @return array
     */
    protected function categoryJSON()
    {
        if (!sessionWithCompilation()) {
            return array();
        }
        $json = array();

        $SQL = SQL::newSelect('category');
        $SQL->addLeftJoin('blog', 'blog_id', 'category_blog_id');
        ACMS_Filter::blogTree($SQL, SBID, 'descendant-or-self');
        ACMS_Filter::categoryKeyword($SQL, $this->keyword);
        $SQL->setLimit($this->limit);
        $all = DB::query($SQL->get(dsn()), 'all');

        foreach ($all as $item) {
            $id = $item['category_id'];
            $bid = $item['category_blog_id'];
            $json[] = array(
                'id' => $id,
                'bid' => $bid,
                'blogName' => ACMS_RAM::blogName($bid),
                'title' => $item['category_name'],
                'subtitle' => $item['category_code'],
                'url' => acmsLink(array(
                    'cid' => $id,
                    'bid' => $bid,
                    'admin' => 'category_edit',
                )),
            );
        }
        return $json;
    }

    /**
     * @return array
     */
    protected function moduleJSON()
    {
        if (!sessionWithAdministration()) {
            return array();
        }
        $json = array();
        $word = '%' . $this->keyword . '%';

        $SQL = SQL::newSelect('module');
        $SQL->addLeftJoin('blog', 'blog_id', 'module_blog_id');
        ACMS_Filter::blogTree($SQL, SBID, 'descendant-or-self');
        $WHERE = SQL::newWhere();
        $WHERE->addWhereOpr('module_identifier', $word, 'LIKE', 'OR');
        $WHERE->addWhereOpr('module_name', $word, 'LIKE', 'OR');
        $WHERE->addWhereOpr('module_label', $word, 'LIKE', 'OR');
        $WHERE->addWhereOpr('module_description', $word, 'LIKE', 'OR');
        $SQL->addWhere($WHERE);
        $SQL->setLimit($this->limit);
        $all = DB::query($SQL->get(dsn()), 'all');

        foreach ($all as $item) {
            $id = $item['module_id'];
            $bid = $item['module_blog_id'];
            $json[] = array(
                'id' => $id,
                'bid' => $bid,
                'blogName' => ACMS_RAM::blogName($bid),
                'title' => $item['module_label'],
                'subtitle' => $item['module_identifier'],
                'url' => acmsLink(array(
                    'bid'   => $bid,
                    'admin' => 'module_edit',
                    'query' => array(
                        'mid' => $id,
                    ),
                )),
            );
        }
        return $json;
    }
}
