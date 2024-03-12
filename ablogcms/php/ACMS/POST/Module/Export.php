<?php

class ACMS_POST_Module_Export extends ACMS_POST_Config_Export
{
    /**
     * @var \Acms\Services\Config\ModuleExport $export
     */
    protected $export;

    /**
     * run
     *
     * @return Field
     */
    public function post()
    {
        @set_time_limit(0);

        if (!$this->checkAuth()) {
            return $this->Post;
        }
        try {
            $mid = $this->Get->get('mid', 0);

            if (empty($mid)) {
                return $this->Post;
            }
            $this->export = App::make('config.export.module');
            $this->export->exportModule(BID, $mid);
            $this->yaml = $this->export->getYaml();
            $this->destPath = ARCHIVES_DIR . 'config.yaml';

            Storage::remove($this->destPath);
            $this->putYaml();

            $module = loadModule($mid);
            AcmsLogger::info('「' . $module->get('label') . '（' . $module->get('identifier') . '）」モジュールをエクスポートしました');

            $this->download();
        } catch (\Exception $e) {
            $this->addError($e->getMessage());
            Storage::remove($this->destPath);

            AcmsLogger::notice('モジュールのエクスポートに失敗しました。' . $e->getMessage(), Common::exceptionArray($e));
        }

        return $this->Post;
    }
}
