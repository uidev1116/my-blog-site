<?php

namespace Acms\Services\Update\System;

use Acms\Services\Facades\Storage;
use Symfony\Component\Finder\Finder;

/**
 * Class PlaceFile
 * @package Acms\Services\Update\System
 */
class PlaceFile
{
    /**
     * @var array
     */
    protected $moveList;

    /**
     * @var array
     */
    protected $exclusionMoveFile;

    /**
     * @var array
     */
    protected $backupList;

    /**
     * @var \Acms\Services\Update\Logger
     */
    protected $logger;

    /**
     * PlaceFile constructor.
     *
     * @param \Acms\Services\Update\Logger $logger
     */
    public function __construct($logger)
    {
        set_time_limit(0);

        $this->logger = $logger;

        $this->moveList = [
            'js' => 'js',
            'lang' => 'lang',
            'php' => 'php',
            'private/config.system.default.yaml' => 'private/config.system.default.yaml',
            'themes/system' => 'themes/system',
            'acms.js' => 'acms.js',
            'index.php' => 'index.php',
            'setup' => '_setup_' . date('YmdHi'),
        ];

        $this->exclusionMoveFile = array_merge(configArray('system_update_ignore'), [
            'php/AAPP',
            'php/ACMS/User',
        ]);

        $this->backupList = [
            'private/config.system.yaml',
            'config.server.php',
            'license.php',
            'extension',
        ];
    }

    /**
     * Validate
     *
     * @param $new_path
     * @param $backup_dir
     * @throws \Exception
     */
    public function validate($new_path, $backup_dir)
    {
        $this->logger->addMessage(gettext('アップデートの検証中...'), 0);
        $validate = true;
        // backup
        foreach ($this->moveList as $item => $to) {
            if ($item === 'setup') {
                continue;
            }
            $path = $backup_dir . $item;
            Storage::makeDirectory(dirname($path));
            if (!Storage::isWritable(dirname($path))) {
                $validate = false;
                $this->logger->error(gettext('書き込み権限がありません。') . ' ' . $path);
            }
        }

        // place file
        foreach ($this->moveList as $from => $to) {
            if (!Storage::exists($to)) {
                $to = dirname($to);
            }
            if (!Storage::isWritable($to)) {
                $validate = false;
                $this->logger->error(gettext('書き込み権限がありません。') . ' ' . $to);
            }
        }
        foreach ($this->exclusionMoveFile as $item) {
            if (!Storage::isWritable($item)) {
                $validate = false;
                $this->logger->error(gettext('書き込み権限がありません。') . ' ' . $item);
            }
        }
        if (!$validate) {
            $this->logger->error(gettext('アップデートの検証に失敗しました。'));
            throw new \RuntimeException('');
        }
        sleep(5);
        $this->logger->addMessage(gettext('アップデートの検証完了'), 0);
    }

    /**
     * Run
     *
     * @param $new_path
     * @param $backup_dir
     * @param $new_setup
     * @throws \Exception
     */
    public function exec($new_path, $backup_dir, $new_setup = false)
    {
        try {
            if ($new_setup) {
                $this->removeSetup();
            } else {
                unset($this->moveList['setup']);
            }
            $this->backup($backup_dir);
            $this->updateFiles($new_path, $backup_dir);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $this->rollback($backup_dir);
            throw new \RuntimeException('');
        }
    }

    /**
     * Backup
     *
     * @param string $backup_dir
     */
    protected function backup($backup_dir)
    {
        Storage::makeDirectory(dirname($backup_dir . 'tmp/'));
        $this->copyFiles($this->exclusionMoveFile, '', $backup_dir . 'tmp/');
        $this->copyFiles($this->backupList, '', $backup_dir);
    }

    /**
     * System Update
     *
     * @param string $new_path
     * @param string $backup_dir
     */
    protected function updateFiles($new_path, $backup_dir)
    {
        $this->logger->addMessage(gettext('システムファイルを展開中...'), 0);
        $base = $new_path . '/';
        $percentage = intval(25 / count($this->moveList));

        foreach ($this->moveList as $from => $to) {
            // 現在のファイルをbackupに退避
            Storage::makeDirectory(dirname($backup_dir . $to));
            Storage::move($to, $backup_dir . $to);

            // Newバージョンのファイルを設置
            if (!Storage::move($base . $from, $to)) {
                throw new \RuntimeException('Could not be moved from ' . $base . $from . ' to ' . $to . '.');
            }
            $this->logger->addPercentage($percentage);
        }
        // アップデートしないファイル（拡張ファイル）を戻す
        $this->copyFiles($this->exclusionMoveFile, $backup_dir . 'tmp/', '');
        $this->logger->addMessage(gettext('システムファイルを展開完了'), 5);
    }

    /**
     * Rollback
     *
     * @param string $backup_dir
     */
    protected function rollback($backup_dir)
    {
        $this->logger->error(gettext('ロールバック中...'));

        foreach ($this->moveList as $item => $to) {
            if ($item === 'setup') {
                continue;
            }
            try {
                Storage::makeDirectory(dirname($backup_dir . 'rollback/' . $to));
                Storage::move($to, $backup_dir . 'rollback/' . $to);
                Storage::move($backup_dir . $item, $item);
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
        $this->logger->error(gettext('ロールバック終了'));
    }

    /**
     * Remove setup
     */
    protected function removeSetup()
    {
        $finder = new Finder();
        $lists = [];
        $iterator = $finder
            ->in('./')
            ->depth('< 2')
            ->name('/^\_setup\_.+/')
            ->directories();

        foreach ($iterator as $dir) {
            $lists[] = $dir->getRelativePathname();
        }
        foreach ($lists as $item) {
            try {
                Storage::removeDirectory($item);
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * Copy files
     *
     * @param array $list
     * @param string $from_dir
     * @param string $to_dir
     */
    protected function copyFiles($list, $from_dir, $to_dir)
    {
        foreach ($list as $item) {
            $from = $from_dir . $item;
            $to = $to_dir . $item;
            Storage::makeDirectory(dirname($to));
            if (is_link($from)) {
                if ($link = readlink($from)) {
                    $from = $link;
                }
            }
            if (Storage::isDirectory($from) && Storage::exists($from)) {
                if (!Storage::copyDirectory($from, $to)) {
                    throw new \RuntimeException('Could not be copied from ' . $from . ' to ' . $to . '.');
                }
            } elseif (Storage::exists($from)) {
                if (!Storage::copy($from, $to)) {
                    throw new \RuntimeException('Could not be copied from ' . $from . ' to ' . $to . '.');
                }
            }
        }
    }
}
