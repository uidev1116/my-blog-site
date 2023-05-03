<?php

namespace Acms\Services\StaticExport;

use App;
use DB;
use SQL;
use Acms\Services\StaticExport\Generator\RequireThemeGenerator;
use Acms\Services\StaticExport\Generator\CategoryGenerator;
use Acms\Services\StaticExport\Generator\EntryGenerator;

class DiffEngine extends Engine
{
    /**
     * @var array
     */
    protected $targetEntries = array();

    /**
     * @var array
     */
    protected $targetCategories = array();

    /**
     * DiffEngine constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Run
     *
     * @param string $from (YYYY-MM-DD HH:ii:ss)
     */
    public function runDiff($from)
    {
        if (!preg_match('/\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}/', $from)) {
            throw new \RuntimeException("Datetime format is invalid.（{$from}）");
        }
        $themes = $this->extractTheme($this->config->theme);

        $this->setDiffItems($from);

        // テーマの必須アセット書き出し
        $this->processExportAssets($themes);

        // テーマの必須テンプレート書き出し
        $this->processExportTheme($themes);

        // トップページの書き出し
        $this->processExportTop();

        // カテゴリートップページの書き出し
        $this->processExportCategoryTop();

        // エントリーの書き出し
        $this->processExportEntry();

        // カテゴリーページの書き出し
        $this->processExportCategoryPagenation(array_intersect($this->config->static_page_cid, $this->targetCategories));

        // アーカイブページの書き出し
        $this->processExportArchivePage(array_intersect($this->config->static_archive_cid, $this->targetCategories));

        $this->logger->start('書き出し完了');
        $this->logger->processing();

        sleep(1);

        $this->logger->destroy();
    }

    /**
     * 指定された日付より新しいエントリーを設定
     *
     * @param string $from (YYYY-MM-DD HH:ii:ss)
     */
    public function setDiffItems($from)
    {
        $SQL = SQL::newSelect('entry');
        $SQL->addSelect('entry_id');
        $SQL->addSelect('entry_category_id');
        $SQL->addLeftJoin('blog', 'blog_id', 'entry_blog_id');
        $SQL->addWhereOpr('entry_updated_datetime', $from, '>=');
        $SQL->addWhereOpr('entry_blog_id', BID);
        $SQL->addWhereOpr('entry_status', 'open');
        $all = DB::query($SQL->get(dsn()), 'all');

        foreach ($all as $entry) {
            $this->targetEntries[] = $entry['entry_id'];
            $this->targetCategories[] = $entry['entry_category_id'];
        }
    }

    /**
     * カテゴリートップの書き出し
     */
    protected function processExportCategoryTop()
    {
        $generator = new CategoryGenerator($this->compiler, $this->destination, $this->logger, $this->maxPublish);
        $generator->setTargetCategories($this->targetCategories);
        $generator->run();
    }

    /**
     * エントリーの書き出し
     */
    protected function processExportEntry()
    {
        $generator = new EntryGenerator($this->compiler, $this->destination, $this->logger, $this->maxPublish);
        $generator->setTargetEntries($this->targetEntries);
        $generator->setWithArchive(true);
        $generator->run();
    }

    /**
     * テーマのアセット書き出し
     *
     * @param array $themes
     */
    protected function processExportAssets($themes)
    {
        $this->copyThemeRequireItems(THEMES_DIR . 'system/');
        foreach ( $themes as $theme ) {
            $path = THEMES_DIR . $theme . '/';
            $this->copyThemeRequireItems($path);
        }
    }

    /**
     *  テーマのテンプレート書き出し
     *
     * @param array $themes
     */
    protected function processExportTheme($themes)
    {
        foreach ($themes as $theme) {
            $path = THEMES_DIR . $theme . '/';
            $themeGenerator = new RequireThemeGenerator($this->compiler, $this->destination, $this->logger, $this->maxPublish);
            $themeGenerator->setSourceTheme($path);
            $themeGenerator->setIncludeList($this->config->include_list);
            $themeGenerator->run();
        }
    }
}

