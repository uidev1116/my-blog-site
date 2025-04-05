<?php

namespace Acms\Services\Facades;

/**
 * @method static int getSummaryRange() サマリーの表示で使うユニットの範囲を取得
 * @method static void setSummaryRange(?int $summaryRange) サマリーの表示で使うユニットの範囲を設定
 * @method static array getUploadedFiles() アップロードされたファイルを取得
 * @method static void addUploadedFiles(string $path) アップロードされたファイルを追加
 * @method static bool isNewVersion() 新規バージョン作成の判定を取得
 * @method static void setNewVersion(bool $flag) 新規バージョン作成の判定をセット
 * @method static bool validEntryCodeDouble(string $code, int $bid, ?int $cid = null, ?int $eid = null) エントリーコードの重複をチェック
 * @method static \Field_Validation validTag(\Field_Validation $Entry) タグの重複をチェック
 * @method static \Field_Validation validSubCategory(\Field_Validation $Entry) サブカテゴリーの重複をチェック
 * @method static bool validateMediaUnit() メディアユニットの重複をチェック
 * @method static void pingTrackback(string $endpoint, int $eid) トラックバックを送信
 * @method static void entryDelete(int $eid, bool $changeRevision = false) エントリーを削除
 * @method static void revisionDelete(int $eid) リビジョンを削除
 * @method static int|false changeRevision(int $rvid, int $eid, int $bid) リビジョンを変更
 * @method static void saveSubCategory(int $eid, int $masterCid, array $cids, ?int $bid = null, ?int $rvid = null) サブカテゴリーを保存
 * @method static string[] getSubCategoryFromString(string $string, string $delimiter = ',') サブカテゴリーを文字列から配列に変換
 * @method static void saveRelatedEntries(int $eid, array $entryAry = [], int $rvid = null, array $typeAry = [], array $loadedTypes = []) 関連エントリーを保存
 * @method static int|false saveEntryRevision(int $eid, int $rvid, array $entryAry, string $type = '', string $memo = '') エントリーのリビジョンを保存
 * @method static bool saveFieldRevision(int $eid, \Field $Field, int $rvid) カスタムフィールドのバージョンを保存
 * @method static bool updateCacheControl(string $start, string $end, ?int $bid = null, ?int $eid = null) キャッシュを更新
 * @method static bool deleteCacheControl(?int $eid = null) キャッシュを削除
 * @method static array getRevision(int $eid, int $rvid) リビジョンを取得
 * @method static bool canUseDirectEdit() 現在のログインユーザーがダイレクト編集を利用可能かどうかを判定する
 * @method static bool isDirectEditEnabled() 現在のログインユーザーのダイレクト編集機能が有効な状態かどうかを判定する
 * @method static bool setTempUnitData(array $data) 一時的にユニットデータを変数に保存
 * @method static array|null getTempUnitData() 一時的に保存したユニットデータを取得
 */
class Entry extends Facade
{
    protected static $instance;

    /**
     * @return string
     */
    protected static function getServiceAlias()
    {
        return 'entry';
    }

    /**
     * @return bool
     */
    protected static function isCache()
    {
        return true;
    }
}
