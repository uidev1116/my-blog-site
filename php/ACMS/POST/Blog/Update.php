<?php

class ACMS_POST_Blog_Update extends ACMS_POST_Blog
{
    function post()
    {
        $Blog = $this->extract('blog');
        $Blog->setMethod('status', 'required');
        $Blog->setMethod('status', 'in', array('open', 'close', 'secret'));
        $Blog->setMethod('status', 'status', Blog::isValidStatus($Blog->get('status'), true));
        $Blog->setMethod('status', 'root', !(RBID == BID and 'close' == $Blog->get('status')));
        $Blog->setMethod('name', 'required');
        $Blog->setMethod('domain', 'required');
        $Blog->setMethod('domain', 'domain', Blog::isDomain($Blog->get('domain'), $this->Get->get('aid'), false, true));
        $Blog->setMethod('code', 'exists', Blog::isCodeExists($Blog->get('domain'), $Blog->get('code'), BID));
        $Blog->setMethod('code', 'reserved', !isReserved($Blog->get('code')));
        $Blog->setMethod('code', 'string', isValidCode($Blog->get('code')));
        $Blog->setMethod('config_set_id', 'value', $this->checkConfigSetScope($Blog->get('config_set_id')));
        $Blog->setMethod('indexing', 'required');
        $Blog->setMethod('indexing', 'in', array('on', 'off'));
        $Blog->setMethod('blog', 'operable', 1
            and (sessionWithAdministration() ? true : Auth::checkShortcut('Blog_Update', ADMIN, 'bid', BID))
        );
        $Blog->validate(new ACMS_Validator());
        $deleteField = new Field();
        $Field  = $this->extract('field', new ACMS_Validator(), $deleteField);
        $Config = $this->extract('config', new ACMS_Validator());
        $Config->validate(new ACMS_Validator());

        if (sessionWithEnterpriseAdministration()) {
            $this->workflowData = $this->extractWorkflow();
        }

        if ( $this->Post->isValidAll() ) {
            $DB = DB::singleton(dsn());

            $status = $Blog->get('status');
            if ( 'open' <> $status ) {
                $aryStatus  = array('open');
                if ( 'close' == $status ) $aryStatus[]  = 'secret';
                $SQL    = SQL::newUpdate('blog');
                $SQL->setUpdate('blog_status', $status);
                $SQL->addWhereIn('blog_status', $aryStatus);
                $SQL->addWhereOpr('blog_left', ACMS_RAM::blogLeft(BID), '>');
                $SQL->addWhereOpr('blog_right', ACMS_RAM::blogRight(BID), '<');
                $DB->query($SQL->get(dsn()), 'exec');
            }

            $setid = $Blog->get('config_set_id');
            if (empty($setid)) {
                $setid = null;
            }

            $SQL = SQL::newUpdate('blog');
            $SQL->addUpdate('blog_status', $status);
            $SQL->addUpdate('blog_name', $Blog->get('name'));
            $SQL->addUpdate('blog_code', trim(strval($Blog->get('code')), '/'));
            $SQL->addUpdate('blog_domain', $Blog->get('domain'));
            $SQL->addUpdate('blog_indexing', strval($Blog->get('indexing')));
            $SQL->addUpdate('blog_config_set_id', $setid);
            $SQL->addWhereOpr('blog_id', BID);
            $DB->query($SQL->get(dsn()), 'exec');

            Cache::flush('temp');

            //-------
            // field
            Common::saveField('bid', BID, $Field, $deleteField);

            //--------
            // config
            Config::saveConfig($Config, BID, null, null, Config::getCurrentConfigSetId());
            Config::set('blog_theme_color', $Config->get('blog_theme_color'));
            Config::set('blog_theme_logo@squarePath', $Config->get('blog_theme_logo@squarePath'));

            //----------
            // geometry
            $this->saveGeometry('bid', BID, $this->extract('geometry'));

            //----------
            // workflow
            if (sessionWithEnterpriseAdministration() && $this->workflowData) {
                $this->saveWorkflow($this->workflowData, BID);
            }

            //------------
            // update RAM
            $SQL    = SQL::newSelect('blog');
            $SQL->addWhereOpr('blog_id', BID);
            if ( $row = $DB->query($SQL->get(dsn()), 'row') ) {
                ACMS_RAM::blog(BID, $row);
            }

            //-------------
            // for display
            $Blog->set('id', BID);

            $this->Post->set('edit', 'update');
            Common::saveFulltext('bid', BID, Common::loadBlogFulltext(BID));
        }

        return $this->Post;
    }
}
