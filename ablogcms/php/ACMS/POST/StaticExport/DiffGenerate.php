<?php

use Acms\Services\Facades\Common;

class ACMS_POST_StaticExport_DiffGenerate extends ACMS_POST_StaticExport_Generate
{
    /**
     * @var \Acms\Services\StaticExport\Compiler
     */
    protected $compiler;

    /**
     * @var \Acms\Services\StaticExport\Destination
     */
    protected $destination;

    /**
     * @var int
     */
    protected $maxPublish;

    /**
     * @var \Acms\Services\StaticExport\Logger
     */
    protected $logger;

    /**
     * @var \Acms\Services\StaticExport\TerminateCheck
     */
    protected $terminateFlag;

    /**
     * Run
     */
    public function post()
    {
        if (!sessionWithAdministration()) die();

        ignore_user_abort(true);
        set_time_limit(0);
        Common::backgroundRedirect(HTTP_REQUEST_URL);
        $this->run();
        die();
    }

    protected function run()
    {
        $setting = Config::loadDefaultField();
        $setting->overload(Config::loadBlogConfig(BID));

        $document_root = $setting->get('static_dest_document_root');
        $offset_dir = $setting->get('static_dest_offset_dir');
        $domain = $setting->get('static_dest_domain');
        $destDiffDir = $setting->get('static_dest_diff');
        $maxPublish = $setting->get('static_max_publish', 3);
        $blogCode = ACMS_RAM::blogCode(BID);
        $config = new stdClass();
        $config->theme = $setting->get('theme');
        $config->static_page_cid = $setting->getArray('static_page_cid');
        $config->static_archive_cid = $setting->getArray('static_archive_cid');
        $config->static_page_max = $setting->getArray('static_page_max');
        $config->static_archive_start = $setting->getArray('static_archive_start');
        $config->static_archive_max = $setting->getArray('static_archive_max');
        $exclusionList = array();
        $includeList = array();
        if ($list = $setting->get('static_export_exclusion_list', false)) {
            $exclusionList = $this->createRegexPathList(preg_split('/(\n|\r|\r\n|\n\r)/', $list));
        }
        if ($list = $setting->get('static_export_include_list', false)) {
            $includeList = $this->createRegexPathList(preg_split('/(\n|\r|\r\n|\n\r)/', $list));
        }
        $config->exclusion_list = $exclusionList;
        $config->include_list = $includeList;

        // 書き出し時間を保存するめに現在時刻を取得
        $exportDate = date('Y-m-d', REQUEST_TIME);
        $exportTime = date('H:i:s', REQUEST_TIME);

        set_time_limit(0);
        DB::setThrowException(true);
        $logger = null;
        try {
            $logger = App::make('static-export.logger');
            $engine = App::make('static-export.diff-engine');
            $destination = App::make('static-export.destination');
            $fromDatetime = $this->Post->get('diff_date') . ' ' . $this->Post->get('diff_time');

            if (0
                || empty($document_root)
                || empty($domain)
                || empty($maxPublish)
            ) {
                throw new \RuntimeException('Configuration is incorrect.');
            }

            $destination->setDestinationDocumentRoot($document_root);
            $destination->setDestinationOffsetDir($offset_dir);
            $destination->setDestinationDiffDir($destDiffDir);
            $destination->setDestinationDomain($domain);
            $destination->setBlogCode($blogCode);
            $logger->initLog();
            $engine->init($logger, $destination, $maxPublish, $config);
            $engine->runDiff($fromDatetime);
            $this->saveExportDatetime($exportDate, $exportTime);

            App::checkException();
        } catch ( \Exception $e ) {
            $logger->error($e->getMessage());
        }
        DB::setThrowException(false);
    }

    protected function saveExportDatetime($date, $time)
    {
        $field = new Field;
        $field->set('static-export-last-time-date', $date);
        $field->set('static-export-last-time-time', $time);

        Config::saveConfig($field, BID);
    }
}
