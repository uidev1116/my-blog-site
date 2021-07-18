<?php

namespace Acms\Services\StaticExport;

use App;
use DB;
use SQL;
use ACMS_Filter;
use Acms\Services\Facades\Storage;
use Acms\Services\StaticExport\Generator\TopGenerator;
use Acms\Services\StaticExport\Generator\ThemeGenerator;
use Acms\Services\StaticExport\Generator\CategoryGenerator;
use Acms\Services\StaticExport\Generator\CategoryPageGenerator;
use Acms\Services\StaticExport\Generator\CategoryArchivesGenerator;
use Acms\Services\StaticExport\Generator\EntryGenerator;
use Acms\Services\StaticExport\Generator\PageGenerator;
use Symfony\Component\Finder\Finder;

class Engine
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
     * @var \Acms\Services\StaticExport\TerminateCheck
     */
    protected $terminateFlag;

    /**
     * @var \Acms\Services\StaticExport\Logger
     */
    protected $logger;

    /**
     * @var \Symfony\Component\Finder\Finder
     */
    protected $finder;

    /**
     * @var int
     */
    protected $maxPublish;

    /**
     * @var \stdClass
     */
    protected $config;


    /**
     * Engine constructor.
     */
    public function __construct()
    {
        $this->finder = new Finder();
    }

    /**
     * 初期設定
     *
     * @param \Acms\Services\StaticExport\Logger $logger
     * @param \Acms\Services\StaticExport\Destination $destination
     * @param int $maxPublish
     * @param \Field $config
     * @throws \Exception
     */
    public function init($logger, $destination, $maxPublish, $config)
    {
        $this->logger = $logger;
        $this->destination = $destination;
        $this->maxPublish = $maxPublish;
        $this->config = $config;

        try {
            if ( !is_writable($this->destination->getDestinationPath()) ) {
                $this->logger->error('データの書き込みに失敗しました。', $this->destination->getDestinationPath());
            }
            $this->compiler = App::make('static-export.compiler');
            $this->compiler->setDestination($this->destination);
        } catch ( \Exception $e ) {
            throw $e;
        }
    }

    /**
     * Run
     */
    public function run()
    {
        $themes = $this->extractTheme($this->config->theme);

        // アセットの書き出し
        $this->processExportArchives();

        // テーマのアセット書き出し
        $this->processExportAssets($themes);

        // css の url属性のパス解決
        $this->processResolvCssPath($themes);

        // テーマのテンプレート書き出し
        $this->processExportTheme($themes);

        // トップページの書き出し
        DB::reconnect(dsn());
        $this->processExportTop();

        // ページの書き出し
        DB::reconnect(dsn());
        $this->processExportPagenation();

        // カテゴリートップページの書き出し
        DB::reconnect(dsn());
        $this->processExportCategoryTop();

        // エントリーの書き出し
        DB::reconnect(dsn());
        $this->processExportEntry();

        // カテゴリーページの書き出し
        DB::reconnect(dsn());
        $this->processExportCategoryPagenation($this->config->static_page_cid);

        // アーカイブページの書き出し
        DB::reconnect(dsn());
        $this->processExportArchivePage($this->config->static_archive_cid);

        // 古いファイルの削除
        $this->deleteOldFiles();

        $this->logger->start('書き出し完了');
        $this->logger->processing();

        sleep(1);

        $this->logger->destroy();
    }

    /**
     * アーカイブの書き出し
     */
    protected function processExportArchives()
    {
        $this->logger->start('アーカイブの書き出し');
        $this->logger->processing();
        $this->copyArchives();
    }

    /**
     * テーマのアセット書き出し
     *
     * @param array $themes
     */
    protected function processExportAssets($themes)
    {
        $this->copyThemeItems(THEMES_DIR . 'system/');
        foreach ( $themes as $theme ) {
            $path = THEMES_DIR . $theme . '/';
            $this->copyThemeItems($path);
        }
    }

    /**
     * css の url属性のパス解決
     *
     * @param array $themes
     */
    protected function processResolvCssPath($themes)
    {
        $this->resolvePathInCss(THEMES_DIR . 'system/');
        foreach ( $themes as $theme ) {
            $path = THEMES_DIR . $theme . '/';
            $this->resolvePathInCss($path);
        }
    }

    /**
     *  テーマのテンプレート書き出し
     *
     * @param array $themes
     */
    protected function processExportTheme($themes)
    {
        foreach ( $themes as $theme ) {
            $path = THEMES_DIR . $theme . '/';
            $themeGenerator = new ThemeGenerator($this->compiler, $this->destination, $this->logger, $this->maxPublish);
            $themeGenerator->setSourceTheme($path);
            $themeGenerator->run();
        }
    }

    /**
     * トップページの書き出し
     */
    protected function processExportTop()
    {
        $generator = new TopGenerator($this->compiler, $this->destination, $this->logger, $this->maxPublish);
        $generator->run();
    }

    /**
     * カテゴリートップの書き出し
     */
    protected function processExportCategoryTop()
    {
        $SQL = SQL::newSelect('category');
        $SQL->setSelect('category_id');
        $SQL->addLeftJoin('blog', 'blog_id', 'category_blog_id');
        ACMS_Filter::blogTree($SQL, BID, 'ancestor-or-self');
        ACMS_Filter::categoryStatus($SQL);
        $Where  = SQL::newWhere();
        $Where->addWhereOpr('category_blog_id', BID, '=', 'OR');
        $Where->addWhereOpr('category_scope', 'global', '=', 'OR');
        $SQL->addWhere($Where);
        $target = DB::query($SQL->get(dsn()), 'list');

        $generator = new CategoryGenerator($this->compiler, $this->destination, $this->logger, $this->maxPublish);
        $generator->setTargetCategories($target);
        $generator->run();
    }

    /**
     * エントリーの書き出し
     */
    protected function processExportEntry()
    {
        $SQL = SQL::newSelect('entry');
        $SQL->setSelect('entry_id');
        $SQL->addLeftJoin('blog', 'blog_id', 'entry_blog_id');
        $SQL->addWhereOpr('entry_blog_id', BID);
        $SQL->addWhereOpr('entry_status', 'open');
        $target = DB::query($SQL->get(dsn()), 'list');

        $generator = new EntryGenerator($this->compiler, $this->destination, $this->logger, $this->maxPublish);
        $generator->setTargetEntries($target);
        $generator->run();
    }

    /**
     * ページの書き出し
     */
    protected function processExportPagenation()
    {
        $this->logger->start('2ページ以降を生成');
        if (!empty($this->config->static_export_dafault_max_page)) {
            $this->logger->processing();
            $generator = new PageGenerator($this->compiler, $this->destination, null, $this->maxPublish);
            $generator->setMaxPage($this->config->static_export_dafault_max_page);
            $generator->run(true);
        }
    }

    /**
     * カテゴリーページの書き出し
     *
     * @param array $categories
     */
    protected function processExportCategoryPagenation($categories)
    {
        $this->logger->start('カテゴリーの2ページ以降を生成', count($categories));
        foreach ( $categories as $i => $cid ) {
            // カテゴリーのページを書き出し
            $this->logger->processing();
            $generator = new CategoryPageGenerator($this->compiler, $this->destination, null, $this->maxPublish);
            $generator->setCategoryId($cid);
            $generator->setMaxPage($this->getConfig('static_page_max', 5, $i));
            $generator->run(true);
        }
    }

    /**
     * アーカイブページの書き出し
     *
     * @param array $categories
     */
    protected function processExportArchivePage($categories)
    {
        foreach ( $categories as $i => $cid ) {
            try {
                $generator = new CategoryArchivesGenerator($this->compiler, $this->destination, $this->logger, $this->maxPublish);
                $generator->setCategoryId($cid);
                $generator->setMonthRange($this->getConfig('static_archive_start', date('Y-m-d', REQUEST_TIME), $i));
                $generator->setMaxPage($this->getConfig('static_archive_max', 5, $i));
                $generator->run();
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage(), $e->getFile() . ':' . $e->getLine());
            }
        }
    }

    /**
     * copy archives
     *
     * @return void
     */
    protected function copyArchives()
    {
        $blog_archives_dir = sprintf('%03d', BID);

        $src_archives_dir = ARCHIVES_DIR . $blog_archives_dir;
        $dest_archives_dir = $this->destination->getDestinationPath() . ARCHIVES_DIR . $blog_archives_dir;
        Storage::copyDirectory($src_archives_dir, $dest_archives_dir);

        $src_media_dir = MEDIA_LIBRARY_DIR . $blog_archives_dir;
        $dest_media_dir = $this->destination->getDestinationPath() . MEDIA_LIBRARY_DIR . $blog_archives_dir;
        Storage::copyDirectory($src_media_dir, $dest_media_dir);

        Storage::copyDirectory(JS_DIR, $this->destination->getDestinationPath() . JS_DIR);
        Storage::copy('acms.js', $this->destination->getDestinationPath() . 'acms.js');
    }

    /**
     * copy theme items
     *
     * @param string $theme
     * @return void
     */
    protected function copyThemeItems($theme)
    {
        if (empty($this)) {
            return;
        }

        $finder = new Finder();
        $iterator = $finder
            ->in($theme)
            ->name('/\.(js|json|css|ttf|img|png|gif|jpeg|jpg|svg|txt|pdf|ppt|xls|csv|docx|pptx|xlsx|zip)$/')
            ->exclude('acms-code')
            ->exclude('admin')
            ->files();

        $this->logger->start('テーマのリソース書き出し ( ' . $theme . ' )', iterator_count($iterator));

        foreach ( $iterator as $file ) {
            try {
                $relative_dir_path = $file->getRelativePath();
                $relative_file_path = $file->getRelativePathname();
                $this->logger->processing();
                Storage::makeDirectory($this->destination->getDestinationPath() . $this->destination->getBlogCode() . $relative_dir_path);
                Storage::copy($theme . $relative_file_path, $this->destination->getDestinationPath() . $this->destination->getBlogCode() . $relative_file_path);
            } catch ( \Exception $e ) {
                $this->logger->error($e->getMessage(), $file->getRelativePathname());
            }
        }
    }

    /**
     * css の url属性のパス解決
     *
     * @param string $theme
     * @return void
     */
    protected function resolvePathInCss($theme)
    {
        $finder = new Finder();
        $iterator = $finder
            ->in($theme)
            ->name('/\.css$/')
            ->exclude('acms-code')
            ->exclude('admin')
            ->files();

        $this->logger->start('CSSのURL属性を解決 ( ' . $theme . ' )', iterator_count($iterator));

        foreach ( $iterator as $file ) {
            $relative_file_path = $file->getRelativePathname();
            $this->logger->processing($relative_file_path);
            if ( $file->isReadable() ) {
                $data = Storage::get($theme . $relative_file_path);
                if ( $data = $this->compiler->compile($data) ) {
                    Storage::put($this->destination->getDestinationPath() . $this->destination->getBlogCode() . $relative_file_path, $data);
                }
            }
        }
    }

    /**
     * delete files
     *
     * @return void
     */
    protected function deleteOldFiles()
    {
        $finder = new Finder();
        $iterator = $finder
            ->in($this->destination->getDestinationPath() . $this->destination->getBlogCode())
            ->notPath(ARCHIVES_DIR)
            ->date('< ' . date('Y-m-d H:i:s', REQUEST_TIME));

        $SQL = SQL::newSelect('blog');
        $SQL->addSelect('blog_code');
        $SQL->addWhereOpr('blog_parent', BID);
        $all = DB::query($SQL->get(dsn()), 'all');
        foreach ($all as $blog) {
            if ($bcd = $blog['blog_code']) {
                $iterator->notPath($bcd);
            }
        }
        if (property_exists($this->config, 'delete_exclusion_list')) {
            foreach ($this->config->delete_exclusion_list as $path) {
                if (!empty($path)) {
                    $iterator->notPath($path);
                }
            }
        }
        $iterator->files();
        $this->logger->start('古いファイルを削除', iterator_count($iterator));

        foreach ( $iterator as $file ) {
            $this->logger->processing();
            $path = $this->destination->getDestinationPath() . $file->getRelativePathname();
            Storage::remove($path);
        }
    }

    /**
     * get themes
     *
     * @param string $theme
     * @return array
     */
    protected function extractTheme($theme)
    {
        $theme = trim($theme, '@');
        $themes[] = $theme;
        while ( $pos = strpos($theme, '@') ) {
            $theme = substr($theme, $pos + 1);
            $themes[] = $theme;
        }
        return array_reverse(array_unique($themes));
    }

    protected function getConfig($key, $default, $i)
    {
        if (property_exists($this->config, $key)) {
            $array = $this->config->$key;
            if (isset($array[$i])) {
                return $array[$i];
            }
        }
        return $default;
    }
}

