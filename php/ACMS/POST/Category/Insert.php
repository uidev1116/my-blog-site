<?php

class ACMS_POST_Category_Insert extends ACMS_POST_Category
{
    function post()
    {
        $Category = $this->extract('category');
        $Category->setMethod('name', 'required');
        $Category->setMethod('code', 'required');
        $Category->setMethod('code', 'double', array($Category->get('scope')));
        $Category->setMethod('code', 'reserved', !isReserved($Category->get('code')));
        $Category->setMethod('code', 'string', isValidCode($Category->get('code')));
        $Category->setMethod('status', 'required');
        $Category->setMethod('status', 'in', array('open', 'close'));
        $Category->setMethod('scope', 'required');
        $Category->setMethod('indexing', 'required');
        $Category->setMethod('indexing', 'in', array('on', 'off'));
        $Category->setMethod('config_set_id', 'value', $this->checkConfigSetScope($Category->get('config_set_id')));

        if ( roleAvailableUser() ) {
            $Category->setMethod('category', 'operable', roleAuthorization('category_create', BID) and IS_LICENSED);
        } else {
            $Category->setMethod('category', 'operable', sessionWithCompilation() and IS_LICENSED);
        }

        if (sessionWithEnterpriseAdministration()) {
            $this->workflowData = $this->extractWorkflow();
        }

        // parentを指定時に、scopeがparentと同じに設定されていなければinvalid
        $Category->setMethod('scope', 'tree',
            !($pid = $Category->get('parent')) ? true : $Category->get('scope') == ACMS_RAM::categoryScope($pid)
        );

        $Category->validate(new ACMS_Validator_Category());
        $Field = $this->extract('field', new ACMS_Validator());

        if ( $this->Post->isValidAll() ) {
            $DB     = DB::singleton(dsn());
            $SQL    = SQL::newSelect('category');
            $SQL->addWhereOpr('category_blog_id', BID);
            $SQL->setOrder('category_right', true);
            $SQL->setLimit(1);
            if ( $row = $DB->query($SQL->get(dsn()), 'row') ) {
                $sort   = $row['category_sort'] + 1;
                $left   = $row['category_right'] + 1;
                $right  = $row['category_right'] + 2;
            } else {
                $sort   = 1;
                $left   = 1;
                $right  = 2;
            }

            $cid    = $DB->query(SQL::nextval('category_id', dsn()), 'seq');
            $code   = $Category->get('code');
            $name   = $Category->get('name');
            $parent = $Category->get('parent');
            $status = $Category->get('status');

            // if parent's status is 'close'. when status force changes to 'close'.
            if ( 'close' == ACMS_RAM::categoryStatus($parent) ) {
                $status = 'close';
            }

            $setid = $Category->get('config_set_id');
            if (empty($setid)) {
                $setid = null;
            }
            $SQL = SQL::newInsert('category');
            $SQL->addInsert('category_id', $cid);
            $SQL->addInsert('category_parent', 0);
            $SQL->addInsert('category_sort', $sort);
            $SQL->addInsert('category_left', $left);
            $SQL->addInsert('category_right', $right);
            $SQL->addInsert('category_blog_id', BID);
            $SQL->addInsert('category_status', $status);
            $SQL->addInsert('category_name', $name);
            $SQL->addInsert('category_scope', $Category->get('scope'));
            $SQL->addInsert('category_indexing', $Category->get('indexing'));
            $SQL->addInsert('category_code', $code);
            $SQL->addInsert('category_config_set_id', $setid);
            $DB->query($SQL->get(dsn()), 'exec');
            Common::saveField('cid', $cid, $Field);

            //----------
            // geometry
            $this->saveGeometry('cid', $cid, $this->extract('geometry'));

            //----------
            // workflow
            if (sessionWithEnterpriseAdministration() && $this->workflowData) {
                $this->saveWorkflow($this->workflowData, BID, $cid);
            }

            // implment ACMS_POST_Category
            $this->changeParentCategory($cid, $parent);

            $Category->set('id', $cid);

            Common::saveFulltext('cid', $cid, Common::loadCategoryFulltext($cid));
            $this->Post->set('edit', 'insert');
        }

        return $this->Post;
    }
}
