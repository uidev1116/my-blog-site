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
         * 例: ブログID = 1のブログの設定に従いページキャッシュを削除する場合
         * ACMS_POST_Cache::clearPageCache(1);
         * 例: エントリーID = 1のエントリーのページキャッシュを削除する場合
         * ACMS_POST_Cache::clearEntryPageCache(1);
         * 例: 全てのキャッシュを削除する場合
         * Cache::allFlush();
         */

        acmsStdMessage('[Success] 処理を完了しました');
    } catch (\Exception $e) {
        acmsStdMessage('[Error] ' . $e->getMessage());
        return false;
    }
    return true;
});
