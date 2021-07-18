<?php

use Acms\Services\Facades\Common;

class ACMS_POST_StaticExport_Generate extends ACMS_POST
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
        $document_root = config('static_dest_document_root');
        $offset_dir = config('static_dest_offset_dir');
        $domain = config('static_dest_domain');
        $maxPublish = config('static_max_publish', 3);
        $blogCode = ACMS_RAM::blogCode(BID);
        $config = new stdClass();
        $config->theme = config('theme');
        $config->static_export_dafault_max_page = config('static_export_dafault_max_page', 0);
        $config->static_page_cid = configArray('static_page_cid');
        $config->static_archive_cid = configArray('static_archive_cid');
        $config->static_page_max = configArray('static_page_max');
        $config->static_archive_start = configArray('static_archive_start');
        $config->static_archive_max = configArray('static_archive_max');
        $exclusionList = array();
        if ($list = config('static_export_delete_exclusion_list', false)) {
            $exclusionList = preg_split('/(\n|\r|\r\n|\n\r)/', $list);
        }
        $config->delete_exclusion_list = $exclusionList;

        set_time_limit(0);
        DB::setThrowException(true);
        $logger = null;
        try {
            $logger = App::make('static-export.logger');
            $engine = App::make('static-export.engine');
            $destination = App::make('static-export.destination');

            if (0
                || empty($document_root)
                || empty($domain)
                || empty($maxPublish)
            ) {
                throw new \RuntimeException('Configuration is incorrect.');
            }

            $destination->setDestinationDocumentRoot($document_root);
            $destination->setDestinationOffsetDir($offset_dir);
            $destination->setDestinationDomain($domain);
            $destination->setBlogCode($blogCode);
            $logger->initLog();
            $engine->init($logger, $destination, $maxPublish, $config);
            $engine->run();
            App::checkException();
        } catch ( \Exception $e ) {
            $logger->error($e->getMessage());
        }
        DB::setThrowException(false);
    }
}