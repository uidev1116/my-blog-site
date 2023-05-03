<?php

class ACMS_POST_Category_Update extends ACMS_POST_Category
{
    function isScopeShared()
    {
        $DB     = DB::persistent(dsn());
        $SQL    = SQL::newSelect('entry');
        $SQL->setSelect('entry_id');
        $SQL->addWhereOpr('entry_category_id', CID);
        $SQL->addWhereOpr('entry_blog_id', BID, '<>');
        $SQL->setLimit(1);
        return !!$DB->query($SQL->get(dsn()), 'one');
    }

    function isSelf($cid, $pid)
    {
        return ($cid <> $pid);
    }

    function isChild($cid, $pid)
    {
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('category');
        $SQL->addWhereOpr('category_id', $pid);
        $SQL->addWhereOpr('category_blog_id', BID);
        $SQL->addWhereOpr('category_left', ACMS_RAM::categoryLeft($cid), '>');
        $SQL->addWhereOpr('category_right', ACMS_RAM::categoryRight($cid), '<');
        return !($DB->query($SQL->get(dsn()), 'one'));
    }

    function post()
    {
        $Category = $this->extract('category');
        $Category->setMethod('name', 'required');
        $Category->setMethod('code', 'required');
        $Category->setMethod('code', 'double', array($Category->get('scope'), CID));
        $Category->setMethod('code', 'reserved', !isReserved($Category->get('code')));
        $Category->setMethod('code', 'string', isValidCode($Category->get('code')));
        $Category->setMethod('status', 'required');
        $Category->setMethod('status', 'in', array('open', 'close'));
        $Category->setMethod('status', 'status');
        $Category->setMethod('scope', 'required');
        $Category->setMethod('indexing', 'required');
        $Category->setMethod('indexing', 'in', array('on', 'off'));
        $Category->setMethod('config_set_id', 'value', $this->checkConfigSetScope($Category->get('config_set_id')));

        if (sessionWithEnterpriseAdministration()) {
            $this->workflowData = $this->extractWorkflow();
        }

        if ( roleAvailableUser() ) {
            $Category->setMethod('category', 'operable', 1
                and !!CID
                and IS_LICENSED
                and roleAuthorization('category_edit', BID)
            );
        } else {
            $Category->setMethod('category', 'operable', 1
                and !!CID
                and IS_LICENSED
                and ( sessionWithCompilation() ? true : Auth::checkShortcut('Category_Update', ADMIN, 'cid', CID) )
            );
        }

        // scopeをlocalに変更しようとしたとき、すでに子ブログのエントリーが登録されていればinvalid
        $Category->setMethod('scope', 'shared', ('local' == $Category->get('scope')) ? !$this->isScopeShared() : true);
        // parentを指定時に、scopeがparentと同じに設定されていなければinvalid
        $Category->setMethod('scope', 'tree',
            !($pid = $Category->get('parent')) ? true : $Category->get('scope') == ACMS_RAM::categoryScope($pid)
        );
        // 自分自身を親カテゴリーに設定しようとするとinvalid
        $Category->setMethod('parent', 'isSelf', $this->isSelf(CID, $pid));
        // 子カテゴリーをparentに設定しようとするとinvalid
        $Category->setMethod('parent', 'isChild', $this->isChild(CID, $pid));

        $Category->validate(new ACMS_Validator_Category());
        $deleteField = new Field();
        $Field = $this->extract('field', new ACMS_Validator(), $deleteField);

        if ( $this->Post->isValidAll() ) {
            $DB     = DB::singleton(dsn());

            $name   = $Category->get('name');
            $code   = $Category->get('code');
            $status = $Category->get('status');
            $scope  = $Category->get('scope');
            $parent = $Category->get('parent');

            // if parent's status is 'close'. when status force changes to 'close'.
            if ( 'close' == ACMS_RAM::categoryStatus($parent) ) {
                $status = 'close';
            }

            if ( 'close' == $status ) {
                $SQL    = SQL::newUpdate('category');
                $SQL->setUpdate('category_status', 'close');
                $SQL->addWhereOpr('category_blog_id', BID);
                $SQL->addWhereOpr('category_left', ACMS_RAM::categoryLeft(CID), '>');
                $SQL->addWhereOpr('category_right', ACMS_RAM::categoryRight(CID), '<');
                $DB->query($SQL->get(dsn()), 'exec');
            }

            $SQL    = SQL::newUpdate('category');
            $SQL->setUpdate('category_scope', $scope);
            $SQL->addWhereOpr('category_blog_id', BID);
            $SQL->addWhereOpr('category_left', ACMS_RAM::categoryLeft(CID), '>');
            $SQL->addWhereOpr('category_right', ACMS_RAM::categoryRight(CID), '<');
            $DB->query($SQL->get(dsn()), 'exec');

            $setid = $Category->get('config_set_id');
            if (empty($setid)) {
                $setid = null;
            }
            $SQL  = SQL::newUpdate('category');
            $SQL->addUpdate('category_status', $status);
            $SQL->addUpdate('category_name', $name);
            $SQL->addUpdate('category_scope', $scope);
            $SQL->addUpdate('category_indexing', $Category->get('indexing'));
            $SQL->addUpdate('category_code', $code);
            $SQL->addUpdate('category_config_set_id', $setid);
            $SQL->addWhereOpr('category_id', CID);
            $DB->query($SQL->get(dsn()), 'exec');
            Common::saveField('cid', CID, $Field, $deleteField);
            //----------
            // geometry
            $this->saveGeometry('cid', CID, $this->extract('geometry'));
            $this->changeParentCategory(CID, $parent);

            //----------
            // workflow
            if (sessionWithEnterpriseAdministration() && $this->workflowData) {
                $this->saveWorkflow($this->workflowData, BID, CID);
            }
        }
        $Category->setField('id', CID);
        Common::saveFulltext('cid', CID, Common::loadCategoryFulltext(CID));
        Cache::flush('temp');

        $this->Post->set('edit', 'update');

        return $this->Post;
    }
}
