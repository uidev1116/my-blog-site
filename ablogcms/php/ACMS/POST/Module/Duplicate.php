<?php

class ACMS_POST_Module_Duplicate extends ACMS_POST_Module
{
    function post()
    {
        $this->Post->setMethod('module', 'midIsNull', ($mid = idval($this->Get->get('mid'))));
        $this->Post->setMethod('module', 'operative', sessionWithAdministration());
        $this->Post->validate();

        if ($this->Post->isValidAll()) {
            $new = $this->dup($mid);

            $module = loadModule($mid);
            AcmsLogger::info('「' . $module->get('label') . '（' . $module->get('identifier') . '）」モジュールを複製しました', [
                'sourceMID' => $mid,
                'createdMID' => $new,
            ]);

            if ($this->Post->get('ajax', false)) {
                die(strval($new));
            }

            // redirect new module_edit
            $url = acmsLink(array(
                'bid' => BID,
                'admin' => 'module_edit',
                'query' => array(
                    'mid' => $new,
                    'edit' => 'update',
                ),
            ));
            $this->redirect($url);
        } else {
            AcmsLogger::info('モジュールの複製に失敗しました', [
                'mid' => $mid,
            ]);
        }

        return $this->Post;
    }
}
