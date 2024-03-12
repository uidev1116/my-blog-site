<?php

class ACMS_POST_Config_Import extends ACMS_POST
{
    /**
     * @return \Field
     */
    function post()
    {
        @set_time_limit(0);

        if (!$this->checkAuth()) {
            return $this->Post;
        }

        try {
            $import = App::make('config.import');
            $yaml = Config::yamlLoad($_FILES['file']['tmp_name']);

            $import->run(BID, $yaml);
            $this->Post->set('notice', $import->getFailedContents());
            $this->Post->set('import', 'success');

            AcmsLogger::info('コンフィグのインポートを実行しました');
        } catch (\Exception $e) {
            $this->addError($e->getMessage());
            AcmsLogger::info('コンフィグのインポートが失敗しました', Common::exceptionArray($e));
        }

        return $this->Post;
    }

    /**
     * check auth
     *
     * @return bool
     */
    protected function checkAuth()
    {
        if (!sessionWithAdministration()) {
            return false;
        }
        if (empty($_FILES['file']['tmp_name'])) {
            $this->addError('No file was uploaded.');
            return false;
        }
        return true;
    }
}
