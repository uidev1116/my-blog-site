<?php

class ACMS_POST_Log_Access_Download extends ACMS_POST
{
    function post()
    {
        if (!sessionWithAdministration()) {
            die();
        }
        if (!IS_LICENSED) {
            die();
        }

        // term selected?
        $start  = $this->Post->get('target_span_start');
        $end    = $this->Post->get('target_span_end');
        if (empty($start) || empty($end)) {
            $this->Post->set('term_not_selected', true);
            return $this->Post;
        }

        @set_time_limit(0);
        $axis   = $this->Post->get('axis', 'self');

        $fds    = [
            'log_access_datetime',
            'log_access_url',
            'log_access_ua',
            'log_access_addr',
            'log_access_referer',
            'log_access_method',
            'log_access_lang',
            'log_access_http_status_code',
            'log_access_publishing',
            'log_access_res_time',
            'log_access_sql_time',
            'log_access_acms_post',
            'log_access_acms_post_valid',
            'log_access_session_uid',
            'log_access_entry_id',
            'log_access_category_id',
            'log_access_user_id',
            'log_access_rule_id',
            'log_access_blog_id',
        ];

        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('log_access');

        foreach ($fds as $fd) {
            $SQL->addSelect($fd);
        }

        // 対象期間の指定
        $SQL->addWhereBw('log_access_datetime', $start, $end);

        // ブログ階層の考慮
        $SQL->addLeftJoin('blog', 'blog_id', 'log_access_blog_id');
        ACMS_Filter::blogTree($SQL, BID, $axis);

        // オーダー＆実行
        $SQL->addOrder('log_access_datetime', 'ASC');
        $q  = $SQL->get(dsn());

        // 書き込み可能？
        if (!Storage::isWritable(ARCHIVES_DIR)) {
            $this->Post->set('archives_not_writable');
            return $this->Post;
        }

        // ファイルを作成
        $file   = 'log_access_' . date('Ymd', strtotime($start)) . '-' . date('Ymd', strtotime($end)) . '.csv';
        $path   = ARCHIVES_DIR . $file;
        $fh     = fopen($path, 'w');

        // 最初の1行目
        $strRow = mb_convert_encoding('"' . implode('","', $fds) . '"' . "\x0d\x0a", 'SJIS-win', 'auto');
        fwrite($fh, $strRow);

        // 全行取得して書き込み
        $DB->query($q, 'fetch');
        while ($row = $DB->fetch($q)) {
            $strRow = mb_convert_encoding('"' . implode('","', $row) . '"' . "\x0d\x0a", 'SJIS-win', 'auto');
            fwrite($fh, $strRow);
        }

        // ファイル処理を終了
        fclose($fh);

        Common::download($path, $file, false, true);
    }
}
