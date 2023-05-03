<?php

class ACMS_POST_Module_Export extends ACMS_POST_Config_Export
{
    /**
     * run
     *
     * @return Field
     */
    public function post()
    {
        @set_time_limit(0);

        if ( !$this->checkAuth() ) {
            return $this->Post;
        }

        try {
            $mid = $this->Get->get('mid', 0);

            if ( empty($mid) ) {
                return $this->Post;
            }

            $this->export = App::make('config.export.module');
            $this->export->exportModule(BID, $mid);
            $this->yaml = $this->export->getYaml();
            $this->destPath = ARCHIVES_DIR . 'config.yaml';

            Storage::remove($this->destPath);
            $this->putYaml();
            $this->download();
        } catch ( \Exception $e ) {
            $this->addError($e->getMessage());
            Storage::remove($this->destPath);
        }

        return $this->Post;
    }
}
