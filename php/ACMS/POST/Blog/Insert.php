<?php

class ACMS_POST_Blog_Insert extends ACMS_POST_Blog
{
    function post()
    {
        $Blog = $this->extract('blog');
        $Blog->setMethod('status', 'required');
        $Blog->setMethod('status', 'in', array('open', 'close', 'secret'));
        $Blog->setMethod('status', 'status', Blog::isValidStatus($Blog->get('status')));
        $Blog->setMethod('status', 'root', true);
        $Blog->setMethod('name', 'required');
        $Blog->setMethod('domain', 'required');
        $Blog->setMethod('domain', 'domain', Blog::isDomain($Blog->get('domain'), $this->Get->get('aid')));
        $Blog->setMethod('code', 'exists', Blog::isCodeExists($Blog->get('domain'), $Blog->get('code')));
        $Blog->setMethod('code', 'reserved', !isReserved($Blog->get('code')));
        $Blog->setMethod('code', 'string', isValidCode($Blog->get('code')));
        $Blog->setMethod('config_set_id', 'value', $this->checkConfigSetScope($Blog->get('config_set_id')));
        $Blog->setMethod('indexing', 'required');
        $Blog->setMethod('indexing', 'in', array('on', 'off'));
        $Blog->setMethod('blog', 'operable', 1
            and sessionWithAdministration()
            and isBlogGlobal(SBID)
            and IS_LICENSED
        );
        $Blog->validate(new ACMS_Validator());
        $Field = $this->extract('field', new ACMS_Validator());
        $Config = $this->extract('config', new ACMS_Validator());

        if (sessionWithEnterpriseAdministration()) {
            $this->workflowData = $this->extractWorkflow();
        }

        if ( $this->Post->isValidAll() ) {
            //-------
            // align
            $DB     = DB::singleton(dsn());
            $SQL    = SQL::newSelect('blog');
            $SQL->addSelect('blog_right');
            $SQL->addSelect('blog_sort');
            $SQL->addWhereOpr('blog_parent', BID);
            $SQL->setOrder('blog_right', 'DESC');
            $SQL->setLimit(1);
            if ( $row = $DB->query($SQL->get(dsn()), 'row') ) {
                $l      = $row['blog_right'] + 1;
                $r      = $l + 1;
                $sort   = $row['blog_sort'] + 1;
            } else {
                $l      = ACMS_RAM::blogRight(BID);
                $r      = $l + 1;
                $sort   = 1;
            }

            //--------
            // adjust
            $SQL    = SQL::newUpdate('blog');
            $SQL->addUpdate('blog_left', SQL::newOpr('blog_left', 2, '+'));
            $SQL->addWhereOpr('blog_left', $l, '>');
            $DB->query($SQL->get(dsn()), 'exec');

            $SQL    = SQL::newUpdate('blog');
            $SQL->addUpdate('blog_right', SQL::newOpr('blog_right', 2, '+'));
            $SQL->addWhereOpr('blog_right', $l, '>=');
            $DB->query($SQL->get(dsn()), 'exec');

            Cache::flush('temp');

            //------
            // insert
            $bid = $DB->query(SQL::nextval('blog_id', dsn()), 'seq');
            $setid = $Blog->get('config_set_id');
            if (empty($setid)) {
                $setid = null;
            }

            $SQL = SQL::newInsert('blog');
            $SQL->addInsert('blog_id', $bid);
            $SQL->addInsert('blog_left', $l);
            $SQL->addInsert('blog_right', $r);
            $SQL->addInsert('blog_sort', $sort);
            $SQL->addInsert('blog_parent', BID);
            $SQL->addInsert('blog_generated_datetime', date('Y-m-d H:i:s', REQUEST_TIME));
            $SQL->addInsert('blog_status', $Blog->get('status'));
            $SQL->addInsert('blog_name', $Blog->get('name'));
            $SQL->addInsert('blog_code', trim(strval($Blog->get('code')), '/'));
            $SQL->addInsert('blog_domain', $Blog->get('domain'));
            $SQL->addInsert('blog_indexing', $Blog->get('indexing'));
            $SQL->addInsert('blog_config_set_id', $setid);
            $DB->query($SQL->get(dsn()), 'exec');

            //-------
            // field
            Common::saveField('bid', $bid, $Field);

            //--------
            // config
            Config::saveConfig($Config, $bid);

            //----------
            // geometry
            $this->saveGeometry('bid', $bid, $this->extract('geometry'));

            //----------
            // workflow
            if (sessionWithEnterpriseAdministration() && $this->workflowData) {
                $this->saveWorkflow($this->workflowData, $bid);
            }

            //-------------
            // for display
            $Blog->set('id', $bid);

            $this->Post->set('edit', 'insert');
            Common::saveFulltext('bid', $bid, Common::loadBlogFulltext($bid));
        }

        return $this->Post;
    }
}
