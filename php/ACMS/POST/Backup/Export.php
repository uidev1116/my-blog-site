<?php

use Acms\Services\Facades\Storage;

class ACMS_POST_Backup_Export extends ACMS_POST_Backup_Base
{
    /**
     * @var \Acms\Services\Database\Replication
     */
    protected $replication;

    /**
     * @var string
     */
    protected $lockFile;

    /**
     * @var string
     */
    protected $tempDirectory;

    /**
     * @var string
     */
    protected $sqlFilePath;

    /**
     * @var string
     */
    protected $hashFilePath;

    /**
     * @return bool|Field
     */
    function post()
    {
        ignore_user_abort(true);
        set_time_limit(0);

        $this->lockFile = CACHE_DIR . 'system-backup-lock';
        $this->tempDirectory =  MEDIA_STORAGE_DIR . '/sql_export/';
        $this->sqlFilePath = $this->tempDirectory . 'sql_query.sql';
        $this->hashFilePath = $this->tempDirectory . 'md5_hash.txt';

        try {
            $this->authCheck('backup_export');

            if (Storage::exists($this->lockFile)) {
                throw new \RuntimeException('データベースのバックアップを中止しました。すでにバックアップ中の可能性があります。変化がない場合は、cache/system-backup-lock ファイルを削除してお試しください。');
            }
            Common::backgroundRedirect(HTTP_REQUEST_URL);
            $this->run();
            die();
        } catch (\Exception $e) {
            $this->addError($e->getMessage());
        }
        return $this->Post;
    }

    /**
     * Run
     */
    protected function run()
    {
        Storage::put($this->lockFile, 'lock');
        set_time_limit(0);
        $logger = App::make('db.logger');
        $this->replication = App::make('db.replication');

        DB::setThrowException(true);
        try {
            $logger->init();
            if (!Storage::makeDirectory($this->tempDirectory)) {
                throw new Exception('ディレクトリの作成に失敗しました。storageディレクトリへの権限を確認して下さい。');
            }
            $this->dumpSql($logger);
            $logger->addMessage('圧縮中...', 0);

            Storage::makeDirectory($this->backupDatabaseDir);
            $dest = $this->backupDatabaseDir . 'database' . date('_Ymd_Hi') . '.zip';
            Storage::compress($this->tempDirectory, $dest, 'backup_tmp');
            Storage::removeDirectory($this->tempDirectory);

            $logger->addMessage('バックアップ完了', 3);
            $logger->success();
        } catch (Exception $e) {
            if ($message = $e->getMessage()) {
                $logger->error($message);
            }
        }
        DB::setThrowException(false);

        Storage::remove($this->lockFile);
        sleep(3);
        $logger->terminate();
    }

    /**
     * dump database
     *
     * @param \Acms\Services\Database\Logger $logger
     * @throws Exception
     */
    protected function dumpSql($logger)
    {
        $logger->addMessage('データベースのバックアップを開始...', 5);
        $except_table = array(
            '/cache/',
            '/log_access/',
            '/session/',
            '/lock/',
            '/lock_source/',
        );

        try {
            $this->replication->authorityValidation();
        } catch (Exception $e) {
            throw $e;
        }

        $not_writable = false;
        if ($tmp_fp = fopen($this->sqlFilePath, 'w')) {
            $logger->addMessage('テーブル構造をエクスポート中...', 5);
            $sql_str = '--Version_' . VERSION . PHP_EOL;
            $sql_str .= '--a blog-cms DB Export' . PHP_EOL;
            $sql_str .= '--' . date("Y/m/d G:i") . PHP_EOL;
            $sql_str .= $this->replication->buildCreateTableSql();
            fwrite($tmp_fp, $this->convertStr($sql_str));

            $tables = $this->replication->getTableList();
            $filteredTables = array();
            foreach ($tables as $table) {
                if (!preg_match('/^' . DB_PREFIX . '*/', $table)) {
                    continue;
                }
                foreach ($except_table as $regex) {
                    if (preg_match($regex, $table)) {
                        continue 2;
                    }
                }
                $filteredTables[] = $table;
            }
            if (empty($filteredTables)) {
                throw new RuntimeException('テーブルの読み込みに失敗しました。');
            }
            $percentage = 85 / count($filteredTables);
            foreach ($filteredTables as $table) {
                $logger->addMessage($table . ' をバックアップ中...', $percentage);
                $this->replication->buildInsertSql($table, $tmp_fp);
            }
        } else {
            $not_writable = true;
        }
        fclose($tmp_fp);

        $logger->addMessage('バックアップ確認用ファイルの生成...', 2);
        if ($hash_fp = fopen($this->hashFilePath, 'w')) {
            $str_md5 = md5_file($this->sqlFilePath);
            $str_md5 = @mb_convert_encoding($str_md5, "UTF-8");
            fwrite($hash_fp, $str_md5);
        } else {
            $not_writable = true;
        }
        fclose($hash_fp);

        if ($not_writable) {
            throw new RuntimeException('ファイルへの書き込に失敗しました。storageディレクトリへの権限を確認して下さい。');
        }
    }

    /**
     * 文字コードの変換
     *
     * @param string $source
     * @return mixed|string
     */
    protected function convertStr($source)
    {
        $source = preg_replace('/' . DB_PREFIX . '/', 'DB_PREFIX_STR_', $source);

        if ('UTF-8' <> DB_CHARSET) {
            $val = @mb_convert_encoding($source, "UTF-8", DB_CHARSET);
            if ($source === mb_convert_encoding($val, DB_CHARSET, 'UTF-8')) {
                $source = $val;
            }
        }
        return $source;
    }
}
