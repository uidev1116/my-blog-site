<?php

class ACMS_GET_Sitemap extends ACMS_GET
{
    public $_axis = [
        'bid'   => 'descendant-or-self',
        'cid'   => 'descendant-or-self',
    ];

    function get()
    {
        $Tpl = new Template($this->tpl);
        $this->buildModuleField($Tpl);
        $DB = DB::singleton(dsn());

        $blogField = new Field_Search(config('sitemap_blog_field'));
        $categoryField = new Field_Search(config('sitemap_category_field'));
        $entryField = new Field_Search(config('sitemap_entry_field'));

        $SQL = SQL::newSelect('blog');
        $SQL->addSelect('blog_id');
        ACMS_Filter::blogTree($SQL, $this->bid, $this->blogAxis());
        $blogArray  = $DB->query($SQL->get(dsn()), 'all');
        $exceptBlog = [];

        foreach ($blogArray as $bid) {
            $bid = $bid['blog_id'];
            $bconf = Config::loadBlogConfigSet($bid);
            if ($bconf->get('feed_output_disable') === 'on') {
                $exceptBlog[] = $bid;
            }
        }

        /**
         * Blog
         */
        $SQL = SQL::newSelect('blog');
        $SQL->setSelect('blog_id');
        ACMS_Filter::blogStatus($SQL);
        ACMS_Filter::blogTree($SQL, $this->bid, $this->blogAxis());
        ACMS_Filter::blogField($SQL, $blogField);

        // indexing
        if ('on' == config('sitemap_blog_indexing')) {
            $SQL->addWhereOpr('blog_indexing', 'on');
        }

        // config（feed_output_disable）で指定されたブログを除外
        $SQL->addWhereNotIn('blog_id', $exceptBlog);

        // order
        $order = config('sitemap_blog_order', 'id-asc');
        ACMS_Filter::blogOrder($SQL, $order);

        $bQ = $SQL->get(dsn());

        if ($DB->query($bQ, 'fetch')) {
            while ($bid = intval(ite($DB->fetch($bQ), 'blog_id'))) {
                $blogData = [
                    'loc' => acmsLink([
                        'bid' => $bid,
                    ], false),
                ];
                $SQL = SQL::newSelect('entry');
                $SQL->addLeftJoin('blog', 'blog_id', 'entry_blog_id');
                $SQL->addSelect('entry_updated_datetime');
                ACMS_Filter::entrySession($SQL);
                ACMS_Filter::blogTree($SQL, $bid, $this->blogAxis());
                if ('on' == config('sitemap_entry_indexing')) {
                    $SQL->addWhereOpr('entry_indexing', 'on');
                }
                $SQL->setOrder('entry_updated_datetime', 'desc');
                if ($lastmod = $DB->query($SQL->get(dsn()), 'one')) {
                    $t = strtotime($lastmod);
                    $lastmod = date('Y-m-d', $t) . 'T' . date('H:i:s', $t) . preg_replace('@(?=\d{2,2}$)@', ':', date('O', $t));
                    $blogData['lastmod'] = $lastmod;
                }

                /**
                 * Category
                 */
                $SQL = SQL::newSelect('category');
                $SQL->setSelect('category_id');
                $SQL->addLeftJoin('blog', 'blog_id', 'category_blog_id');

                ACMS_Filter::blogTree($SQL, $bid, 'ancestor-or-self');
                ACMS_Filter::categoryStatus($SQL);

                ACMS_Filter::categoryField($SQL, $categoryField);
                $Where = SQL::newWhere();
                $Where->addWhereOpr('category_blog_id', $bid, '=', 'OR');
                $Where->addWhereOpr('category_scope', 'global', '=', 'OR');
                $SQL->addWhere($Where);

                // indexing
                if ('on' == config('sitemap_category_indexing')) {
                    $SQL->addWhereOpr('category_indexing', 'on');
                }

                $order = config('sitemap_category_order', 'id-asc');
                list($sort) = explode('-', $order);
                if ($sort === 'amount') {
                    $SQL->addLeftJoin('entry', 'entry_category_id', 'category_id');
                    $Where = SQL::newWhere();
                    ACMS_Filter::entrySession($Where);
                    $Case = SQL::newCase();
                    $Case->add($Where, 1);
                    $Case->setElse('NULL');
                    $SQL->addSelect($Case, 'category_entry_amount', null, 'count');
                    $SQL->setGroup('category_id');
                }
                // order
                ACMS_Filter::categoryOrder($SQL, $order);
                $cQ = $SQL->get(dsn());
                $DB->query($cQ, 'fetch');
                $cid = null;
                $blogHasEmptyEntryCode = false;

                do {
                    $categoryHasEmptyEntryCode = false;
                    $categoryData = null;
                    if (!empty($cid)) {
                        $data = [
                            'loc' => acmsLink([
                                'bid' => $bid,
                                'cid' => $cid,
                            ], false),
                        ];
                        $SQL = SQL::newSelect('entry');
                        $SQL->addLeftJoin('category', 'category_id', 'entry_category_id');
                        $SQL->addSelect('entry_updated_datetime');
                        ACMS_Filter::entrySession($SQL);
                        ACMS_Filter::categoryTree($SQL, $cid, $this->categoryAxis());
                        $SQL->addWhereOpr('entry_blog_id', $bid);
                        if ('on' == config('sitemap_entry_indexing')) {
                            $SQL->addWhereOpr('entry_indexing', 'on');
                        }
                        $SQL->setOrder('entry_updated_datetime', 'desc');
                        if ($lastmod = $DB->query($SQL->get(dsn()), 'one')) {
                            $t = strtotime($lastmod);
                            $lastmod = date('Y-m-d', $t) . 'T' . date('H:i:s', $t) . preg_replace('@(?=\d{2,2}$)@', ':', date('O', $t));
                            $data['lastmod'] = $lastmod;
                        }
                        $categoryData = $data;
                    }

                    /**
                     * Entry
                     */
                    $SQL = SQL::newSelect('entry');
                    $SQL->addSelect('entry_id');
                    $SQL->addSelect('entry_code');
                    $SQL->addSelect('entry_updated_datetime');
                    ACMS_Filter::entrySession($SQL);
                    ACMS_Filter::entryField($SQL, $entryField);
                    $SQL->addWhereOpr('entry_category_id', $cid);
                    $SQL->addWhereOpr('entry_blog_id', $bid);

                    // indexing
                    if ('on' == config('sitemap_entry_indexing')) {
                        $SQL->addWhereOpr('entry_indexing', 'on');
                    }

                    // order
                    $order = config('sitemap_entry_order', 'id-asc');
                    ACMS_Filter::entryOrder($SQL, $order);

                    // limit
                    if (!!($limit = config('sitemap_entry_limit')) && $limit != 0) {
                        $SQL->setLimit($limit);
                    }

                    $eQ = $SQL->get(dsn());
                    if (!$DB->query($eQ, 'fetch')) {
                        break;
                    }

                    while ($row = $DB->fetch($eQ)) {
                        $ecd = $row['entry_code'];
                        if (!$ecd && $cid) {
                            $categoryHasEmptyEntryCode = true;
                        } elseif (!$ecd) {
                            $blogHasEmptyEntryCode = true;
                        }
                        $eid = intval($row['entry_id']);
                        $t = strtotime($row['entry_updated_datetime']);
                        $lastmod = date('Y-m-d', $t) . 'T' . date('H:i:s', $t) . preg_replace('@(?=\d{2,2}$)@', ':', date('O', $t));
                        $Tpl->add('url:loop', [
                            'loc' => acmsLink([
                                'bid' => $bid,
                                'cid' => $cid,
                                'eid' => $eid,
                            ], false),
                            'lastmod' => $lastmod,
                        ]);
                    }

                    if (!empty($cid) && !$categoryHasEmptyEntryCode) {
                        $Tpl->add('url:loop', $categoryData);
                    }
                } while ($cid = intval(ite($DB->fetch($cQ), 'category_id')));

                if (!$blogHasEmptyEntryCode) {
                    $Tpl->add('url:loop', $blogData);
                }
            }
        }
        return $Tpl->get();
    }
}
