<?php

class ACMS_POST_Config_Export extends ACMS_POST
{
    /**
     * @var string $yaml
     */
    protected $yaml;

    /**
     * @var string $destPath
     */
    protected $destPath;

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
            $this->export = App::make('config.export');
            $this->export->exportAll(BID);
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

    /**
     * download yaml data
     *
     * @return void
     */
    protected function download()
    {
        if ( !Storage::exists($this->destPath) ) {
            throw new RuntimeException('Can not read a yaml to a file.');
        }
        $file = 'config' . date('_Ymd_Hi') . '.yaml';
        Common::download($this->destPath, $file, false, true);
    }

    /**
     * write a yaml to a file
     *
     * @return void
     *
     * @throws RuntimeException
     */
    protected function putYaml()
    {
        if ( !Storage::put($this->destPath, $this->yaml) ) {
            throw new RuntimeException('Can not write a yaml to a file.');
        }
    }

    /**
     * check auth
     *
     * @return bool
     */
    protected function checkAuth()
    {
        return sessionWithAdministration();
    }
}
