<?php

class ACMS_GET_Ajax_ArgReference extends ACMS_GET_Admin
{
    function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $DB     = DB::singleton(dsn());

        $scope  = $this->Get->get('scope');
        $root   = $scope . ':touch';

        switch ($scope) {
            case 'bid':
                $this->buildBlogSelect($Tpl, BID, null, 'blog:loop', true, true, 'sort-asc');
                break;
            case 'cid':
            case 'ccd':
                // blog:loop
                $SQL    = SQL::newSelect('blog');
                ACMS_Filter::blogTree($SQL, BID, 'descendant-or-self');
                $blogs  = $DB->query($SQL->get(dsn()), 'all');
                foreach ($blogs as $blog) {
                    $Tpl->add(['blog:loop', $root], $blog);
                }

                // current blog's & inherit category:loop
                $this->buildCategorySelect($Tpl, BID, null, ['category:loop', 'lineage:loop', $root], true);
                $Tpl->add(['lineage:loop', $root], ['bid' => BID]);

                // linege blog's category:loop
                foreach ($blogs as $blog) {
                    $bid    = $blog['blog_id'];
                    if ($bid == BID) {
                        continue;
                    }
                    $this->buildCategorySelect($Tpl, $bid, null, ['category:loop', 'lineage:loop', $root], true);
                    $Tpl->add(['lineage:loop', $root], ['bid' => $bid]);
                }

                break;
            case 'uid':
            case 'ucd':
                $SQL    = SQL::newSelect('user');
                $SQL->addLeftJoin('blog', 'blog_id', 'user_blog_id');
                $SQL->addWhereOpr('blog_left', ACMS_RAM::blogLeft(BID), '>=');
                $SQL->addWhereOpr('blog_right', ACMS_RAM::blogRight(BID), '<=');
                $all  = $DB->query($SQL->get(dsn()), 'all');
                $blogs  = [];
                $users  = [];
                foreach ($all as $row) {
                    $users[$row['user_blog_id']][]  = $row;
                    $blogs[$row['user_blog_id']]    = $row;
                }
                foreach ($users as $bid => $us) {
                    foreach ($us as $u) {
                        $Tpl->add(['user:loop', 'lineage:loop', $root], $u);
                    }
                    $Tpl->add(['lineage:loop', $root], $blogs[$bid]);
                }
                foreach ($blogs as $blog) {
                    $Tpl->add(['blog:loop', $root], $blog);
                }
                break;
            case 'eid':
                $SQL    = SQL::newSelect('entry');
                break;
        }

        $Tpl->add($root);
        return $Tpl->get();
    }
}
