<?php

// 設置場所に合わせて、php/standalone のパスを合わせてください。
require_once dirname(__FILE__) . '/../php/standalone.php';

set_time_limit(0);
ini_set('memory_limit', '512M');

acmsStandAloneRun(function () {
    acmsStdMessage('[Start] 処理を開始しました');
    try {
        /**
         * ここに処理を追加
         *
         * 例: キャッシュを削除する場合
         * ACMS_POST_Cache::clear('self-or-descendant');
         * ACMS_POST_Cache::refresh('self-or-descendant');
         */

        acmsStdMessage('[Success] 処理を完了しました');
    } catch (\Exception $e) {
        acmsStdMessage('[Error] ' . $e->getMessage());
        return false;
    }
    return true;
});
