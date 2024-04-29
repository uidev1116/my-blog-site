<?php

class ACMS_GET_Topicpath extends ACMS_GET
{
    public $_axis = [ // phpcs:ignore
        'bid'   => 'descendant-or-self',
        'cid'   => 'descendant-or-self',
    ];

    public $_scope = [ // phpcs:ignore
        'uid'       => 'global',
        'cid'       => 'global',
        'eid'       => 'global',
        'keyword'   => 'global',
        'tag'       => 'global',
        'field'     => 'global',
        'date'      => 'global',
        'start'     => 'global',
        'end'       => 'global',
        'page'      => 'global',
    ];

    public function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $this->buildModuleField($Tpl);
        $DB     = DB::singleton(dsn());
        $cnt    = 0;
        $loop   = 1;

        //------
        // blog
        if ('0' !== strval(config('mo_topicpath_blog_limit'))) {
            $SQL    = SQL::newSelect('blog');
            ACMS_Filter::blogTree(
                $SQL,
                $this->bid,
                str_replace('descendant', 'ancestor', $this->blogAxis())
            );
            ACMS_Filter::blogStatus($SQL);
            $SQL->setOrder('blog_left', ('top' == config('mo_topicpath_blog_base')) ? 'ASC' : 'DESC');

            //----------
            // indexing
            $Case   = SQL::newCase();
            $Case->add(SQL::newOpr('blog_id', $this->bid), 1);
            $Case->add(SQL::newOpr('blog_indexing', 'on'), 1);
            $Case->setElse(0);
            $SQL->addWhere($Case);

            //-------
            // limit
            if ($blimit = intval(config('mo_topicpath_blog_limit'))) {
                $SQL->setLimit($blimit);
            }

            $all    = $DB->query($SQL->get(dsn()), 'all');
            if (
                0
                or ( 1
                    and 'top' == config('mo_topicpath_blog_base')
                    and 'desc' == config('mo_topicpath_blog_order')
                )
                or ( 1
                    and 'bottom' == config('mo_topicpath_blog_base')
                    and 'asc' == config('mo_topicpath_blog_order')
                )
            ) {
                $all    = array_reverse($all);
            }

            foreach ($all as $i => $row) {
                if (!empty($cnt)) {
                    $Tpl->add(['glue', 'blog:loop']);
                } elseif (!!($altLabel = config('mo_topicpath_root_label'))) {
                    $row['blog_name'] = $altLabel;
                }
                $bid    = intval($row['blog_id']);
                if ('on' === config('mo_topicpath_blog_field')) {
                    $Tpl->add(['blogField', 'blog:loop'], $this->buildField(loadBlogField($bid), $Tpl));
                }
                $Tpl->add('blog:loop', [
                    'name' => $row['blog_name'],
                    'url'   => acmsLink([
                        'bid'   => $bid,
                    ]),
                    'sNum' => $loop,
                ]);
                $cnt++;
                $loop++;
            }
        }

        //----------
        // category
        if (!empty($this->cid) and '0' !== strval(config('mo_topicpath_category_limit'))) {
            $SQL    = SQL::newSelect('category');
            ACMS_Filter::categoryTree(
                $SQL,
                $this->cid,
                str_replace('descendant', 'ancestor', $this->categoryAxis())
            );
            ACMS_Filter::categoryStatus($SQL);
            $SQL->setOrder('category_left', ('top' == config('mo_topicpath_category_base')) ? 'ASC' : 'DESC');

            //----------
            // indexing
            $Case   = SQL::newCase();
            if (!empty($this->cid)) {
                $Case->add(SQL::newOpr('category_id', $this->cid), 1);
            }
            $Case->add(SQL::newOpr('category_indexing', 'on'), 1);
            $Case->setElse(0);
            $SQL->addWhere($Case);

            //-------
            // limit
            if ($climit = intval(config('mo_topicpath_category_limit'))) {
                $SQL->setLimit($climit);
            }

            $all    = $DB->query($SQL->get(dsn()), 'all');
            if (
                0
                or ( 1
                    and 'top' == config('mo_topicpath_category_base')
                    and 'desc' == config('mo_topicpath_category_order')
                )
                or ( 1
                    and 'bottom' == config('mo_topicpath_category_base')
                    and 'asc' == config('mo_topicpath_category_order')
                )
            ) {
                $all    = array_reverse($all);
            }

            foreach ($all as $i => $row) {
                if (!empty($cnt)) {
                    $Tpl->add(['glue', 'category:loop']);
                }

                $cid    = intval($row['category_id']);
                if ('on' === config('mo_topicpath_category_field')) {
                    $Tpl->add(['categoryField', 'category:loop'], $this->buildField(loadCategoryField($cid), $Tpl));
                }

                $Tpl->add('category:loop', [
                    'name'  => $row['category_name'],
                    'url'   => acmsLink([
                        'bid'   => $this->bid,
                        'cid'   => $cid,
                    ]),
                    'sNum' => $loop,
                ]);
                $cnt++;
                $loop++;
            }
        }

        //-------
        // entry
        if (!empty($this->eid) and 'on' == config('mo_topicpath_entry')) {
            $SQL    = SQL::newSelect('entry');
            $SQL->addWhereOpr('entry_id', $this->eid);
            $row    = $DB->query($SQL->get(dsn()), 'row');
            if (empty($row['entry_code']) and 'on' == config('mo_topicpath_ignore_ecdempty')) {
                // ignore block
            } else {
                if (!empty($cnt)) {
                    $Tpl->add(['glue', 'entry']);
                }

                $eid    = intval($row['entry_id']);
                if ('on' === config('mo_topicpath_entry_field')) {
                    $Tpl->add(['entryField', 'entry'], $this->buildField(loadEntryField($eid), $Tpl));
                }

                $Tpl->add('entry', [
                    'title' => $row['entry_title'],
                    'url'   => acmsLink([
                        'bid'   => $this->bid,
                        'eid'   => $eid,
                    ]),
                    'sNum' => $loop,
                ]);
                $loop++;
            }
        }

        return $Tpl->get();
    }
}
