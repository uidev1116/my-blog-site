<?php

class ACMS_POST_Category_Index_ConfigSet extends ACMS_POST
{
    function post()
    {
        $aryCid = $this->Post->getArray('checks');
        $setid = $this->Post->get('config_set_id') ?: null;
        if (empty($setid)) {
            $setid = null;
        }

        $this->Post->reset(true);
        $this->Post->setMethod('category', 'operable', ( 1
            and sessionWithCompilation()
            and !empty($aryCid)
        ));
        $this->Post->validate();

        if ($this->Post->isValidAll()) {
            $DB = DB::singleton(dsn());
            $SQL = SQL::newUpdate('category');
            $SQL->setUpdate('category_config_set_id', $setid);
            $SQL->addWhereIn('category_id', $aryCid);
            $DB->query($SQL->get(dsn()), 'exec');
            foreach ($aryCid as $cid) {
                ACMS_RAM::category($cid, null);
            }
            $sql = SQL::newSelect('config_set');
            $sql->setSelect('config_set_name');
            $sql->addWhereOpr('config_set_id', $setid);
            $name = DB::query($sql->get(dsn()), 'one');
            if (empty($name)) {
                $name = '設定なし';
            }
            AcmsLogger::info('指定されたカテゴリーのコンフィグセットを「' . $name . '」に変更', [
                'targetCIDs' => implode(',', $aryCid),
                'configSetID' => $setid,
            ]);
        } else {
            AcmsLogger::info('カテゴリーのコンフィグセット変更に失敗しました', [
                'targetCIDs' => implode(',', $aryCid),
                'configSetID' => $setid,
                'validator' => $this->Post->_aryV
            ]);
        }
        return $this->Post;
    }
}
