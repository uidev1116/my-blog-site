<?php

namespace Acms\Custom;

/**
 * ユーザー定義のHookを設定します。
 */
class Hook
{
    /**
     * header指定
     *
     * @param bool $cache キャッシュ利用
     */
    public function header($cache)
    {
//        header('Vary: User-Agent');
//        header('Vary: Accept-Encoding');
//        header('Vary: Accept-Language');
//        header('Vary: Cookie');
    }

    /**
     * クエリ発行前
     *
     * @param string $sql
     */
    public function query(&$sql)
    {

    }

    /**
     * ルール判定のカスタム値
     *
     * @param string $value
     */
    public function customRuleValue(& $value)
    {
        // ここで設定した値を、ルール判定に使用できるようになります。
        $value = '';
    }

    /**
     * キャッシュルールに特殊ルールを追加
     *
     * @param string $customRuleString
     */
    public function addCacheRule(&$customRuleString)
    {
//        $customRuleString = UA_GROUP; // デバイスによってルールを分ける場合
    }

    /**
     * GETモジュール処理前
     * 解決前テンプレートの中間処理など
     *
     * @param string &$tpl
     * @param \ACMS_GET $thisModule
     */
    public function beforeGetFire(&$tpl, $thisModule)
    {

    }

    /**
     * GETモジュール処理後
     * 解決済みテンプレートの中間処理など
     *
     * @param string &$res
     * @param \ACMS_GET $thisModule
     */
    public function afterGetFire(&$res, $thisModule)
    {

    }

    /**
     * POSTモジュール処理前
     * $thisModuleのプロパティを参照・操作するなど
     *
     * @param \ACMS_POST $thisModule
     */
    public function beforePostFire($thisModule)
    {

    }

    /**
     * POSTモジュール処理後
     * $thisModuleのプロパティを参照・操作するなど
     *
     * @param \ACMS_POST $thisModule
     */
    public function afterPostFire($thisModule)
    {

    }

    /**
     * ビルド前（GETモジュール解決前）
     *
     * @param $tpl &$tpl テンプレート文字列
     */
    public function beforeBuild(&$tpl)
    {

    }

    /**
     * ビルド後（GETモジュール解決後）
     * ※ 空白の除去・文字コードの変換・POSTモジュールに対するSIDの割り当てなどはこの後に行われます
     *
     * @param string &$res レスポンス文字列
     */
    public function afterBuild(&$res)
    {

    }

    /**
     * エントリー作成、更新時 または エントリーインポート時（CSV, WordPress, Movable Type）
     *
     * @param int $eid エントリーID
     * @param int $revisionId リビジョンID
     */
    public function saveEntry($eid, $revisionId)
    {

    }

    /**
     * フォーム Submit時
     *
     * @param array $mail 自動返信メール
     * @param array $mailAdmin 管理者宛メール
     */
    public function formSubmit($mail, $mailAdmin)
    {

    }

    /**
     * 承認通知
     *
     * @param array $data 通知データ
     * @param bool falseを設定するとデフォルトのメールが飛ばないように設定
     */
    public function approvalNotification($data, &$send = true)
    {

    }

    /**
     * 処理の一番最後のシャットダウン時
     *
     *
     */
    public function beforeShutdown()
    {

    }

    /**
     * グローバル変数の拡張
     *
     * @param array $globalVars
     */
    public function extendsGlobalVars(&$globalVars)
    {
        // $globalVars->set('key', 'var');
    }

    /**
     * 引用ユニット拡張
     * @param string $url 引用URL
     * @param string &$html 整形後HTML
     */
    public function extendsQuoteUnit($url, &$html)
    {
//        if ( preg_match("/thebase\.in\/items\/([\d]+)$/", $url, $matches) ) {
//            try {
//                $item_id = $matches[1];
//                $client = AAPP_Base_GET_Base_Api::create();
//                $json = $client->get('items/detail/' . $item_id, array());
//                $tpl = <<< EOT
//
//<!-- BEGIN item -->
//<blockquote class="js-biggerlink">
//    <div class="quoteImageContainer">
//        <img src="{img1_300}" class="quoteImage" width="154" alt="" />
//    </div>
//    <div>
//        <p class="quoteTitle"><a href="https://ablogcms.thebase.in/items/{item_id}" class="quoteTitleLink">{title}</a></p>
//        <p class="quoteSiteName">{detail}</p>
//        <p class="quoteDescription">{quote_description2}[trim(180, '...')]</p>
//    </div>
//    <hr class="clearHidden" />
//</blockquote>
//<!-- END item -->
//EOT;
//                $Tpl = new Template($tpl, new ACMS_Corrector());
//                $html = $Tpl->render(array(
//                    'item' => $json->item,
//                ));
//            } catch ( Exception $e ) {}
//
//        }

        // $Amazon = new ACMS_Services_Amazon(
        //     'tracking_id',
        //     'access_key',
        //     'secret_access_key'
        // );
        // if ( 1
        //     && $Amazon->isValid()
        //     && $asin = $Amazon->getAsinFromUrl($url)
        // ) {
        //     $xml            = $Amazon->amazonItemLookup($asin);

        //     $url            = $xml->Items->Item->DetailPageURL;
        //     $image          = $xml->Items->Item->LargeImage->URL;
        //     $manufacturer   = $xml->Items->Item->ItemAttributes->Manufacturer;
        //     $title          = $xml->Items->Item->ItemAttributes->Title;
        //     $price          = $xml->Items->Item->OfferSummary->LowestNewPrice->FormattedPrice;

        //     $html = "<h1><a href=\"$url\">$title</a></h1>"
        //         ."<img src=\"$image\" width=\"150px\">"
        //         ."<p>$manufacturer</p>"
        //         ."<p>$price</p>";

        //     sleep(2);
        // }
    }

    /**
     * ビデオユニット拡張
     * @param string $url URL
     * @param string &$id Video ID
     */
    public function extendsVideoUnit($url, &$id)
    {
        // $parsed_url = parse_url($url);
        // if ( !empty($parsed_url['path']) ) {
        //     $id = preg_replace('@/@', '', $parsed_url['path']);
        // }
    }

    /**
     * キャッシュのリフレッシュ時
     *
     */
    public function cacheRefresh()
    {

    }

    /**
     * キャッシュのクリア時
     *
     */
    public function cacheClear()
    {

    }

    /**
     * キャッシュの削除時
     *
     */
    public function cacheDelete()
    {

    }

    /**
     * メディアデータ作成
     * @param string $path 作成先パス
     *
     */
    public function mediaCreate($path)
    {

    }

    /**
     * メディアデータ削除
     * @param string $path 削除パス
     *
     */
    public function mediaDelete($path)
    {

    }
}
