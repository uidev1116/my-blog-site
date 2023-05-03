<?php

class ACMS_POST_Config_Set_Duplicate extends ACMS_POST
{
    function post()
    {
        try {
            $this->validate();
            $db = DB::singleton(dsn());
            $configSetId = $this->Post->get('config_set_id');
            $name = gettext('このブログの初期コンフィグ')  . config('entry_title_duplicate_suffix');
            $scope = 'local';

            if ($configSetId) {
                $configSet = $this->getConfigSet($configSetId);
                $name = $configSet['config_set_name'] . config('entry_title_duplicate_suffix');
                $scope = $configSet['config_set_scope'];
            }

            $newSetId = $db->query(SQL::nextval('config_set_id', dsn()), 'seq');
            $sql = SQL::newInsert('config_set');
            $sql->addInsert('config_set_id', $newSetId);
            $sql->addInsert('config_set_sort', $this->getConfigSetSort());
            $sql->addInsert('config_set_name', $name);
            $sql->addInsert('config_set_description', '');
            $sql->addInsert('config_set_scope',$scope);
            $sql->addInsert('config_set_blog_id', BID);
            $db->query($sql->get(dsn()), 'exec');

            $this->copyConfig($configSetId, $newSetId);

            $this->addMessage(gettext('コンフィグセットを複製しました。'));

        } catch (\Exception $e) {
            $this->addError($e->getMessage());
        }
        return $this->Post;
    }

    protected function copyConfig($id, $newId)
    {
        $db = DB::singleton(dsn());
        $sql = SQL::newSelect('config');
        if (empty($id)) {
            $sql->addWhereOpr('config_set_id', null);
            $sql->addWhereOpr('config_blog_id', BID);

        } else {
            $sql->addWhereOpr('config_set_id', $id);
        }
        $q = $sql->get(dsn());
        $db->query($q, 'fetch');

        while ($config = $db->fetch($q)) {
            $insert = SQL::newInsert('config');
            foreach (array_keys($config) as $key) {
                if ($key === 'config_set_id') {
                    continue;
                }
                if ($key === 'config_blog_id') {
                    continue;
                }
                $insert->addInsert($key, $config[$key]);
            }
            $insert->addInsert('config_set_id', $newId);
            $insert->addInsert('config_blog_id', BID);
            $db->query($insert->get(dsn()), 'exec');
        }
    }

    protected function getConfigSet($id)
    {
        $sql = SQL::newSelect('config_set');
        $sql->addWhereOpr('config_set_id', $id);
        $config = DB::query($sql->get(dsn()), 'row');

        if (empty($config)) {
            throw new \RuntimeException('Not found config set.');
        }
        return $config;
    }

    protected function getConfigSetSort()
    {
        $sql = SQL::newSelect('config_set');
        $sql->setSelect('config_set_sort');
        $sql->addWhereOpr('config_set_blog_id', BID);
        $sql->setOrder('config_set_sort', 'DESC');

        return max(intval(DB::query($sql->get(dsn()), 'one')), ACMS_RAM::blogAliasSort(BID)) + 1;
    }

    protected function validate()
    {
        if (!sessionWithAdministration()) {
            throw new \RuntimeException('Permission denied.');
        }
    }
}

