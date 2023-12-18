<?php

class ACMS_POST_Config_Set_Delete extends ACMS_POST_Config_Set_Insert
{
    protected function validate($setid)
    {
        $this->Post->setMethod('config_set', 'operable', $setid && sessionWithAdministration());

        if ($setid) {
            // blogに指定されていないかチェック
            $sql = SQL::newSelect('blog');
            $sql->addSelect('blog_config_set_id');
            $where = SQL::newWhere();
            $where->addWhereOpr('blog_config_set_id', $setid, '=', 'OR');
            $where->addWhereOpr('blog_theme_set_id', $setid, '=', 'OR');
            $where->addWhereOpr('blog_editor_set_id', $setid, '=', 'OR');
            $sql->addWhere($where);
            if (DB::query($sql->get(dsn()), 'one')) {
                $this->Post->setMethod('config_set', 'used', false);
            }

            // categoryに指定されていないかチェック
            $sql = SQL::newSelect('category');
            $sql->addSelect('category_config_set_id');
            $where = SQL::newWhere();
            $where->addWhereOpr('category_config_set_id', $setid, '=', 'OR');
            $where->addWhereOpr('category_theme_set_id', $setid, '=', 'OR');
            $where->addWhereOpr('category_editor_set_id', $setid, '=', 'OR');
            $sql->addWhere($where);
            if (DB::query($sql->get(dsn()), 'one')) {
                $this->Post->setMethod('config_set', 'used', false);
            }
        }

        $this->Post->validate();

        return $this->Post->isValidAll();
    }

    function post()
    {
        $setid = intval($this->Get->get('setid'));
        $type = $this->Post->get('type', null);
        $configSetName = ACMS_RAM::configSetName($setid);

        if (!$this->validate($setid)) {
            $label = $this->getLogName($type);
            AcmsLogger::info('「' . $configSetName . '」' . $label . 'の削除に失敗しました', [
                'Post' => $this->Post->_aryV,
            ]);
            return $this->Post;
        }

        $DB = DB::singleton(dsn());

        $SQL = SQL::newSelect('config_set');
        $SQL->addWhereOpr('config_set_id', $setid);
        $SQL->addWhereOpr('config_set_blog_id', BID);
        $configSet = $DB->query($SQL->get(dsn()), 'row');

        if (!$configSet) {
            return $this->Post;
        }

        // update sort
        $sort = $configSet['config_set_sort'];
        $SQL = SQL::newUpdate('config_set');
        $SQL->setUpdate('config_set_sort', SQL::newOpr('config_set_sort', 1, '-'));
        $SQL->addWhereOpr('config_set_sort', $sort, '>');
        $DB->query($SQL->get(dsn()), 'exec');

        // delete
        $SQL = SQL::newDelete('config_set');
        $SQL->addWhereOpr('config_set_id', $setid);
        $SQL->addWhereOpr('config_set_blog_id', BID);
        $DB->query($SQL->get(dsn()), 'exec');

        // delete config
        $SQL = SQL::newDelete('config');
        $SQL->addWhereOpr('config_set_id', $setid);
        $DB->query($SQL->get(dsn()), 'exec');

        Config::forgetCache(BID, null, null, $setid);

        $this->Post->set('edit', 'delete');

        $label = $this->getLogName($type);
        AcmsLogger::info('「' . $configSetName . '」' . $label . 'の削除をしました');

        return $this->Post;
    }
}
