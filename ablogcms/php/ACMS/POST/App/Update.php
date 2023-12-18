<?php

class ACMS_POST_App_Update extends ACMS_POST
{
    public function post()
    {
        $appClassName = $this->Post->get('class_name');

        if (!sessionWithAdministration()) {
            return $this->Post;
        }
        if (!class_exists($appClassName)) {
            return $this->Post;
        }

        $App = new $appClassName();

        try {
            $className = get_class($App);
            if ($className) {
                $App->update();
                $DB = DB::singleton(dsn());
                $SQL = SQL::newUpdate('app');
                $SQL->addUpdate('app_version', $App->version);
                $SQL->addWhereOpr('app_name', $className);
                $DB->query($SQL->get(dsn()), 'exec');

                Cache::flush('template');
                Cache::flush('config');
                Cache::flush('field');
                Cache::flush('temp');

                $this->Post->set('updateSucceed', true);

                AcmsLogger::info('拡張アプリ「' . $className . '」をアップデートしました', [
                    'version' => $App->version,
                ]);
            }
        } catch(Exception $e) {
            $this->Post->set('updateFailed', true);

            AcmsLogger::info('拡張アプリ「' . $className . '」のアップデートに失敗しました', Common::exceptionArray($e, ['version' => $App->version]));
        }

        return $this->Post;
    }
}
