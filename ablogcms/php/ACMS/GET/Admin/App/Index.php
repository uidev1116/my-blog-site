<?php

use Acms\Services\Facades\Storage;

class ACMS_GET_Admin_App_Index extends ACMS_GET_Admin
{
    /**
     * @var bool
     */
    protected $isNotFound = true;

    /**
     * main
     *
     * @return string
     */
    function get()
    {
        if ('app_index' <> ADMIN) {
            return '';
        }
        if (!sessionWithAdministration()) {
            return '';
        }

        $Tpl = new Template($this->tpl, new ACMS_Corrector());

        $apps = array_merge($this->getAppList(), $this->getLegacyAppList());
        $this->build($Tpl, $apps);

        if ($this->isNotFound) {
            $Tpl->add('notFound');
        }
        if (!$this->Post->isNull()) {
            $this->Post->set('notice_mess', 'show');
        }

        $Tpl->add(null, $this->buildField($this->Post, $Tpl));

        return $Tpl->get();
    }

    /**
     * @return array
     */
    protected function getAppList()
    {
        $apps = array();
        if (!Storage::exists(PLUGIN_LIB_DIR)) {
            return $apps;
        }
        $list = scandir(PLUGIN_LIB_DIR);

        foreach ($list as $fd) {
            if (!Storage::isDirectory(PLUGIN_LIB_DIR . $fd)) {
                continue;
            }
            $namespace = 'Acms\\Plugins\\' . $fd;
            $provider = $namespace . '\\ServiceProvider';
            if (!class_exists($provider)) {
                continue;
            }
            $app = new $provider();
            if (!$app instanceof ACMS_App) {
                continue;
            }
            $app->className = $provider;
            $apps[] = $app;
        }
        return $apps;
    }

    /**
     * @return array
     */
    protected function getLegacyAppList()
    {
        $apps = array();
        $list = scandir(AAPP_LIB_DIR);
        if (!Storage::exists(AAPP_LIB_DIR)) {
            return $apps;
        }

        foreach ($list as $fd) {

            if (Storage::isFile(AAPP_LIB_DIR . $fd)) {
                $className = 'AAPP_' . str_replace('.php', '', $fd);
                if (!class_exists($className)) {
                    continue;
                }
                $app = new $className();
                if (!$app instanceof ACMS_App) {
                    continue;
                }
                $app->className = $className;
                $apps[] = $app;
            }
        }
        return $apps;
    }

    /**
     * @param $Tpl
     * @param $lists
     */
    protected function build($Tpl, $lists)
    {
        $DB = DB::singleton(dsn());

        foreach ($lists as $app) {
            $className = $app->className;

            $SQL = SQL::newSelect('app');
            $SQL->addWhereOpr('app_name', $className);

            // DBになければインストール前として扱う
            $status = 'init';

            if (!!($all = $DB->query($SQL->get(dsn()), 'all'))) {
                $existsOnThisBlog = false;
                $installedVersion = null;
                foreach ($all as $row) {
                    if (intval($row['app_blog_id']) === BID) {
                        $existsOnThisBlog = $row;
                    }
                    $installedVersion = $row['app_version'];
                }
                if ($existsOnThisBlog) {
                    $status = $existsOnThisBlog['app_status'];
                } else {
                    $status = 'off';
                }
                if (version_compare($installedVersion, $app->version)) {
                    $status = 'update';
                }
            }

            // ルートブログ以外では、インストールされていないアプリは表示しない
            if ($status === 'init' && RBID !== BID) {
                continue;
            }

            $this->isNotFound = false;
            $Tpl->add('status:touch#' . $status);
            $Tpl->add('action:touch#' . $status);
            $Tpl->add('app:loop', (array)$app);
        }
    }
}
