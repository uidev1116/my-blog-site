<?php

class ACMS_POST_Config_Set_Update extends ACMS_POST_Config_Set_Insert
{
    function post()
    {
        $setid = intval($this->Get->get('setid'));
        $type = $this->Post->get('type', null);

        $configSet = $this->extract('config_set');
        $configSet->setMethod('name', 'required');
        $configSet->setMethod('alias', 'operable', IS_LICENSED and sessionWithAdministration() and $setid);
        $configSet->validate(new ACMS_Validator());

        if ($this->Post->isValidAll()) {
            $DB = DB::singleton(dsn());
            $SQL = SQL::newUpdate('config_set');
            $SQL->addUpdate('config_set_name', $configSet->get('name'));
            $SQL->addUpdate('config_set_description', $configSet->get('description'));
            $SQL->addUpdate('config_set_scope', $configSet->get('scope', 'local'));
            $SQL->addUpdate('config_set_blog_id', BID);
            $SQL->addWhereOpr('config_set_blog_id', BID);
            $SQL->addWhereOpr('config_set_id', $setid);
            $DB->query($SQL->get(dsn()), 'exec');

            $this->Post->set('edit', 'update');

            Config::forgetConfigSetNameCache($setid);

            $label = $this->getLogName($type);
            AcmsLogger::info('「' . $configSet->get('name') . '」' . $label . 'を更新しました', [
                'name' => $configSet->get('name'),
                'description' => $configSet->get('description'),
                'scope' => $configSet->get('scope', 'local'),
            ]);
        } else {
            $label = $this->getLogName($type);
            AcmsLogger::info('「' . ACMS_RAM::configSetName($setid) . '」' . $label . 'の更新に失敗しました');
        }
        return $this->Post;
    }
}
