<?php

class ACMS_POST_Module_Import extends ACMS_POST
{
    /**
     * @return \Field
     */
    function post()
    {
        @set_time_limit(0);

        if ( !$this->checkAuth() ) {
            return $this->Post;
        }

        try {
            $import = App::make('config.import.module');
            $yaml = Config::yamlLoad($_FILES['file']['tmp_name']);

            $import->run(BID, $yaml);
            $this->Post->set('notice', $import->getFailedContents());
            $this->Post->set('import', 'success');
        } catch ( \Exception $e ) {
            $this->addError($e->getMessage());
        }

        return $this->Post;
    }

    /**
     * check auth
     *
     * @return bool
     */
    private function checkAuth()
    {
        if ( !sessionWithAdministration() ) return false;
        if ( empty($_FILES['file']['tmp_name']) ) {
            $this->addError('No file was uploaded.');
            return false;
        }
        return true;
    }
}
