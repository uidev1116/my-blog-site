<?php

class ACMS_POST_Module_Delete extends ACMS_POST_Module
{
    function post()
    {
        $Module = $this->extract('module');
        $Module->reset();
        $this->Post->setMethod('module', 'midIsNull', ($mid = idval($this->Get->get('mid'))));

        if (roleAvailableUser()) {
            $this->Post->setMethod('module', 'operative', roleAuthorization('module_edit', BID));
        } else {
            $this->Post->setMethod('module', 'operative', sessionWithAdministration());
        }
        $this->Post->validate();

        if ($this->Post->isValidAll()) {
            $this->delete($mid);

            AcmsLogger::info('「' . $Module->get('label') . '（' . $Module->get('identifier') . '）」モジュールを削除しました', [
                'mid' => $mid,
            ]);
        } else {
            AcmsLogger::info('モジュールの削除に失敗しました', [
                'mid' => $mid,
            ]);
        }

        return $this->Post;
    }

    function delete($mid)
    {
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newDelete('module');
        $SQL->addWhereOpr('module_id', $mid);
        $SQL->addWhereOpr('module_blog_id', BID);
        $DB->query($SQL->get(dsn()), 'exec');

        //--------
        // delete
        $SQL    = SQL::newDelete('config');
        $SQL->addWhereOpr('config_module_id', $mid);
        $SQL->addWhereOpr('config_blog_id', BID);
        $DB->query($SQL->get(dsn()), 'exec');

        Config::forgetCache(BID, null, $mid);

        $this->Post->set('edit', 'delete');
    }
}
