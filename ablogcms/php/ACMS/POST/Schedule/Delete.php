<?php

class ACMS_POST_Schedule_Delete extends ACMS_POST_Schedule
{
    public function post()
    {
        $DB = DB::singleton(dsn());
        $scid = $this->Get->get('scid');

        $SQL = SQL::newSelect('schedule');
        $SQL->setSelect('schedule_name');
        $SQL->addWhereOpr('schedule_id', $scid);
        $name = $DB->query($SQL->get(dsn()), 'one');

        if (!sessionWithScheduleAdministration()) {
            AcmsLogger::info('権限がないため「' . $name . '」スケジュールセットの削除に失敗しました', [
                'scid' => $scid,
            ]);
            return $this->Post;
        }

        $SQL    = SQL::newDelete('schedule');
        $SQL->addWhereOpr('schedule_id', $scid);
        $SQL->addWhereOpr('schedule_blog_id', BID);
        $DB->query($SQL->get(dsn()), 'exec');

        $SQL    = SQL::newDelete('config');
        $SQL->addWhereOpr('config_key', 'schedule_label@' . $scid);
        $SQL->addWhereOpr('config_blog_id', BID);
        $DB->query($SQL->get(dsn()), 'exec');

        Config::forgetCache(BID);

        $this->Post->set('edit', 'delete');

        AcmsLogger::info('「' . $name . '」スケジュールセットを削除しました', [
            'scid' => $scid,
        ]);

        return $this->Post;
    }
}
