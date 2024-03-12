<?php

class ACMS_POST_Form_Delete extends ACMS_POST_Form
{
    function post()
    {
        $this->Post->setMethod('form', 'fmidIsNull', ($fmid = intval($this->Get->get('fmid'))));
        if (roleAvailableUser()) {
            $this->Post->setMethod('form', 'operative', roleAuthorization('form_edit', BID));
        } else {
            $this->Post->setMethod('form', 'operative', sessionWithFormAdministration());
        }
        $this->Post->validate();

        $formName = ACMS_RAM::formName($fmid);
        $formCode = ACMS_RAM::formCode($fmid);

        if ($this->Post->isValidAll()) {
            $DB = DB::singleton(dsn());
            $SQL = SQL::newDelete('form');
            $SQL->addWhereOpr('form_id', $fmid);
            $SQL->addWhereOpr('form_blog_id', BID);
            $DB->query($SQL->get(dsn()), 'exec');

            $DB = DB::singleton(dsn());
            $SQL = SQL::newDelete('log_form');
            $SQL->addWhereOpr('log_form_form_id', $fmid);
            $SQL->addWhereOpr('log_form_blog_id', BID);
            $DB->query($SQL->get(dsn()), 'exec');

            $this->Post->set('edit', 'delete');
            AcmsLogger::info('フォームID「' . $formName . '（' . $formCode . '）」を削除しました');
        } else {
            AcmsLogger::info('フォームID「' . $formName . '（' . $formCode . '）」の削除に失敗しました', [
                'Post' => $this->Post,
            ]);
        }

        return $this->Post;
    }
}
