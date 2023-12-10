<?php

class ACMS_POST_Module_Index_Delete extends ACMS_POST_Module_Delete
{
    function post()
    {
        $this->Post->reset(true);
        $this->Post->setMethod('checks', 'required');

        if ( enableApproval(BID, CID) ) {
            $this->Post->setMethod('module', 'operative', sessionWithApprovalAdministrator(BID, CID));
        } else if ( roleAvailableUser() ) {
            $this->Post->setMethod('module', 'operative', roleAuthorization('module_edit', BID));
        } else {
            $this->Post->setMethod('module', 'operative', sessionWithContribution());
        }
        $this->Post->validate(new ACMS_Validator());

        $targetModules = [];

        if ($this->Post->isValidAll()) {
            @set_time_limit(0);
            foreach ( $this->Post->getArray('checks') as $mid ) {
                $id = preg_split('@:@', $mid, 2, PREG_SPLIT_NO_EMPTY);
                $mid = $id[1];
                $module = loadModule($mid);
                $this->delete($mid);

                $targetModules[] = $module->get('label') . '（' . $module->get('identifier') . '）';
            }
            $this->Post->set('refreshed', 'refreshed');

            AcmsLogger::info('選択したモジュールIDを削除しました', [
                'targetModules' => $targetModules,
            ]);
        } else {
            AcmsLogger::info('選択したモジュールIDの削除に失敗しました');
        }

        return $this->Post;
    }
}
