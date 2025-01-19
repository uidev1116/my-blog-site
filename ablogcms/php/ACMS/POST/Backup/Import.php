<?php

use Acms\Services\Facades\Storage;
use Acms\Services\Facades\Cache;
use Symfony\Component\Finder\Finder;

class ACMS_POST_Backup_Import extends ACMS_POST_Backup_Base
{
    /**
     * @var \Acms\Services\Database\Replication
     */
    protected $replication;

    /**
     * @var string
     */
    protected $versionCheck;

    /**
     * @var string
     */
    protected $backupTempDir;

    /**
     * @var array
     */
    protected $errorMsg = [];

    /**
     * @inheritDoc
     */
    public function post()
    {
        try {
            $this->authCheck('backup_import');

            AcmsLogger::info('データベースのリストアを開始しました');

            ignore_user_abort(true);
            set_time_limit(0);

            $this->backupTempDir = MEDIA_STORAGE_DIR . 'backup_tmp/';
            $this->replication = App::make('db.replication');
            $this->versionCheck = $this->Post->get('version_check');

            $this->decompress();
            $hashFilePath = $this->backupTempDir . 'md5_hash.txt';
            $hash = Storage::get($hashFilePath, dirname($hashFilePath));

            if ($this->fileHashTest($this->backupTempDir . 'sql_query.sql', $hash)) {
                if ($sql_fp = fopen($this->backupTempDir . 'sql_query.sql', 'r')) {
                    sleep(3);
                    $this->readLineSql(trim(fgets($sql_fp)));
                    $this->validation();

                    Common::backgroundRedirect(acmsLink(['bid' => RBID]));
                    $this->run($sql_fp);
                    die();
                } else {
                    throw new Exception('ファイルを読み込めませんでした。権限を確認して下さい。');
                }
            } else {
                throw new Exception('エクスポートファイルが違うか壊れています。');
            }
        } catch (Exception $e) {
            $this->addError($e->getMessage());
            AcmsLogger::warning('データベースのインポートでエラーが発生しました。', Common::exceptionArray($e));

            return $this->Post;
        }
    }

    /**
     * @param $sql_fp
     * @throws Exception
     */
    protected function run($sql_fp)
    {
        sleep(5);

        while ($sql = fgets($sql_fp)) {
            $this->readLineSql($sql);
        }
        fclose($sql_fp);
        $this->replication->rewriteDomain(DOMAIN, DB_PREFIX . 'blog');
        if ($this->Post->get('drop_table') === 'on') {
            $this->replication->dropCashTable();
        }
        Storage::removeDirectory($this->backupTempDir);

        Cache::flush('template');
        Cache::flush('config');
        Cache::flush('field');
        Cache::flush('temp');

        $field = new Field();
        $field->set('backupFileName', $this->Post->get('sqlfile'));
        $this->notify($field);
    }

    /**
     * @throws Exception
     */
    protected function validation()
    {
        try {
            $this->replication->authorityValidation();
            $this->replication->dropCashTable();
            $this->replication->renameAllTable();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @param $file_path
     * @param $hash
     * @return bool
     */
    protected function fileHashTest($file_path, $hash)
    {
        $file_hash = md5_file($file_path);
        $file_hash = @mb_convert_encoding($file_hash, "UTF-8");
        if ($file_hash === $hash) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $line
     * @throws Exception
     */
    protected function readLineSql($line)
    {
        $line = str_replace('DB_PREFIX_STR_', DB_PREFIX, $line);
        $line = mb_convert_encoding($line, DB_CHARSET, "UTF-8");

        if (substr($line, 0, 2) === '--') {
            if ($this->versionCheck === 'on' and preg_match('/^--Version_.*/', $line)) {
                $version = trim(substr($line, 2));
                $srv_ver = 'Version_' . VERSION;
                if ($version !== $srv_ver) {
                    throw new Exception("バージョンが違います。$version にアップグレードまたはダウングレードしてからインポートして下さい。現在のバージョン $srv_ver");
                }
            }
        } else {
            $pattern = '/^(CREATE TABLE|INSERT INTO).*;$/is';
            if (preg_match($pattern, $line)) {
                $line = rtrim($line, ';');
                DB::query($line, 'exec');
            }
        }
    }

    /**
     * decompress
     */
    protected function decompress()
    {
        $file_name = $this->Post->get('sqlfile', false);
        if (empty($file_name) || !Storage::exists($this->backupDatabaseDir . $file_name)) {
            throw new \RuntimeException('無効なファイルです。DBエクスポートファイルを選択して下さい。');
        }
        Storage::removeDirectory($this->backupTempDir);
        Storage::unzip($this->backupDatabaseDir . $file_name, MEDIA_STORAGE_DIR);

        if (!Storage::exists($this->backupTempDir . 'sql_query.sql')) {
            $finder = new Finder();
            $iterator = $finder
                ->in($this->backupTempDir)
                ->depth('< 2')
                ->name('/(md5_hash\.txt|sql_query\.sql)$/');
            Storage::makeDirectory($this->backupTempDir);
            foreach ($iterator as $file) {
                $filename = basename(str_replace('\\', '/', $file->getFilename()));
                Storage::move($this->backupTempDir . $file->getRelativePathname(), $this->backupTempDir . $filename);
            }
        }
    }

    /**
     * @param $field
     * @throws Exception
     */
    protected function notify($field)
    {
        if (
            1
            and $subjectTpl = findTemplate('mail/restore/subject.txt')
            and $bodyTpl = findTemplate('mail/restore/body.txt')
        ) {
            $subject = Common::getMailTxt($subjectTpl, $field);
            $body = Common::getMailTxt($bodyTpl, $field);
            try {
                $mailer = Mailer::init();
                $mailer = $mailer->setFrom(ACMS_RAM::userMail(SUID))
                    ->setTo(ACMS_RAM::userMail(SUID))
                    ->setSubject($subject)
                    ->setBody($body);

                if ($bodyHtmlTpl = findTemplate('mail/restore/body.html')) {
                    $bodyHtml = Common::getMailTxt($bodyHtmlTpl, $field);
                    $mailer = $mailer->setHtml($bodyHtml);
                }
                $mailer->send();
            } catch (Exception $e) {
                AcmsLogger::warning('データベースのインポート完了通知メールの送信に失敗しました', Common::exceptionArray($e));
            }
        }
    }
}
