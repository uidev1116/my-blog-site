<?php

namespace Acms\Services\StaticExport;

use DB;
use SQL;
use Acms\Services\StaticExport\Generator\RequireThemeGenerator;
use Acms\Services\StaticExport\Generator\CategoryGenerator;
use Acms\Services\StaticExport\Generator\EntryGenerator;
use Acms\Services\Facades\Common;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

use function React\Async\await;

class DiffEngine extends Engine
{
    /**
     * @var int[]
     */
    protected $targetEntryIds = [];

    /**
     * @var int[]
     */
    protected $targetCategoryIds = [];

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

        try {
            // テーマの必須アセット書き出し
            $this->processExportThemeAssets($themes);

            // テーマの必須テンプレート書き出し
            $this->processExportTheme($themes);

            // トップページの書き出し
            DB::reconnect(dsn());
            await($this->processExportTop());

            // カテゴリートップページの書き出し
            DB::reconnect(dsn());
            await($this->processExportCategoryTop());

            // エントリーの書き出し
            DB::reconnect(dsn());
            await($this->processExportEntry());

            // カテゴリーページの書き出し
            DB::reconnect(dsn());
            await($this->processExportCategoryPagenation(array_intersect($this->config->static_page_cid, $this->targetCategoryIds)));

            // カテゴリーアーカイブページの書き出し
            DB::reconnect(dsn());
            await($this->processExportCategoryArchivePage(array_intersect($this->config->static_archive_cid, $this->targetCategoryIds)));
        } catch (\Throwable $th) {
            $this->logger->error('不明なエラーが発生したため、部分書き出し処理を中断します');
            throw $th;
        } finally {
            $this->logger->start('書き出し完了');
            $this->logger->processing();

            sleep(1);

            $this->logger->destroy();
        }
    }

    /**
     * 指定された日付より新しいエントリーを設定
     *
     * @param string $from (YYYY-MM-DD HH:ii:ss)
     */
    private function setDiffItems($from)
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
            $this->targetEntryIds[] = intval($entry['entry_id']);
            $this->targetCategoryIds[] = intval($entry['entry_category_id']);
        }
    }

    /**
     * @inheritDoc
     */
    protected function processExportCategoryTop(): PromiseInterface
    {
        return new Promise(
            function (callable $resolve) {
                $generator = new CategoryGenerator(
                    $this->compiler,
                    $this->destination,
                    $this->logger,
                    $this->maxPublish,
                    $this->nameServer
                );
                $generator->setCategoryIds($this->targetCategoryIds);
                try {
                    await($generator->run());
                } catch (\Throwable $th) {
                    $this->logger->error('不明なエラーが発生したため、カテゴリートップページの書き出しを中断します');
                    \AcmsLogger::error('カテゴリートップページの部分静的書き出しに失敗しました。', Common::exceptionArray($th));
                }
                $resolve(null);
            }
        );
    }

    /**
     * @inheritDoc
     */
    protected function processExportEntry(): PromiseInterface
    {
        return new Promise(
            function (callable $resolve) {
                $generator = new EntryGenerator(
                    $this->compiler,
                    $this->destination,
                    $this->logger,
                    $this->maxPublish,
                    $this->nameServer
                );
                $generator->setEntryIds($this->targetEntryIds);
                $generator->setWithArchive(true);
                try {
                    await($generator->run());
                } catch (\Throwable $th) {
                    $this->logger->error('不明なエラーが発生したため、エントリーの書き出しを中断します');
                    \AcmsLogger::error('エントリーの部分静的書き出しに失敗しました。', Common::exceptionArray($th));
                }
                $resolve(null);
            }
        );
    }

    /**
     * @inheritDoc
     */
    protected function processExportThemeAssets($themes)
    {
        $this->copyThemeRequireItems(THEMES_DIR . 'system/');
        foreach ($themes as $theme) {
            $path = THEMES_DIR . $theme . '/';
            $this->copyThemeRequireItems($path);
        }
    }

    /**
     *  テーマのテンプレート書き出し
     *
     * @inheritDoc
     */
    protected function processExportTheme($themes): PromiseInterface
    {
        return new Promise(
            function (callable $resolve) use ($themes) {
                foreach ($themes as $theme) {
                    $path = THEMES_DIR . $theme . '/';
                    $requireThemeGenerator = new RequireThemeGenerator(
                        $this->compiler,
                        $this->destination,
                        $this->logger,
                        $this->maxPublish,
                        $this->nameServer
                    );
                    $requireThemeGenerator->setSourceTheme($path);
                    $requireThemeGenerator->setIncludeList($this->config->include_list);
                    try {
                        await($requireThemeGenerator->run());
                    } catch (\Throwable $th) {
                        $this->logger->error('不明なエラーが発生したため、「' . $theme . '」の必須テンプレートの書き出しを中断します');
                        \AcmsLogger::error('「' . $theme . '」の必須テンプレートの部分静的書き出しに失敗しました。', Common::exceptionArray($th));
                    }
                }
                $resolve(null);
            }
        );
    }
}
