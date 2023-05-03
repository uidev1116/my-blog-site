<?php

class ACMS_POST_Module_Index_Export extends ACMS_POST_Config_Export
{
    /**
     * run
     *
     * @return Field
     */
    public function post()
    {
        @set_time_limit(0);

        if ( enableApproval(BID, null) ) {
            $this->Post->setMethod('module', 'operative', sessionWithApprovalAdministrator(BID, CID));
        } else if ( roleAvailableUser() ) {
            $this->Post->setMethod('module', 'operative', roleAuthorization('entry_edit', BID));
        } else {
            $this->Post->setMethod('module', 'operative', sessionWithAdministration());
        }

        $this->Post->setMethod('checks', 'required');
        $this->Post->validate(new ACMS_Validator());

        if ( !$this->Post->isValidAll() ) {
            return $this->Post;
        }

        try {
            $this->export = App::make('config.export.module');
            foreach ( $this->Post->getArray('checks') as $mid ) {
                $id     = preg_split('@:@', $mid, 2, PREG_SPLIT_NO_EMPTY);
                $bid    = $id[0];
                $mid    = $id[1];
                if ( $bid != BID && empty($mid) ) {
                    continue;
                }
                $this->export->exportModule(BID, $mid);
            }
            $this->yaml = $this->export->getYaml();
            $this->destPath = ARCHIVES_DIR . 'config.yaml';

            Storage::remove($this->destPath);
            $this->putYaml();
            $this->download();
        } catch ( \Exception $e ) {
            var_dump($e->getMessage());
            $this->addError($e->getMessage());
            Storage::remove($this->destPath);
        }

        return $this->Post;
    }
}
