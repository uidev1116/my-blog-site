<?php

namespace Acms\Services\Preview\Contracts;

interface Base
{
    /**
     * プレビューモード中か判定
     *
     * @return bool
     */
    public function isPreviewMode();

    /**
     * 偽装ユーザーエージェントの取得
     *
     * @return string|false
     */
    public function getFakeUserAgent();

    /**
     * プレビュー共有モードになれるか判定
     *
     * @return bool
     */
    public function isValidPreviewSharingUrl();

    /**
     * プレビュー共有URLの取得
     *
     * @param string $url
     * @return string
     */
    public function getShareUrl($url, $lifetime = false);

    /**
     * 共有URLで実際に表示するiFrameのURL
     *
     * @return string
     */
    public function getSharePreviewUrl();

    /**
     * 期限切れの共有URLを削除
     */
    public function expiredShareUrl();

    /**
     * プレビューモードを開始
     *
     * @param string $fakeUserAgent
     * @param string $token
     * @return void
     */
    public function startPreviewMode($fakeUserAgent, $token);

    /**
     * プレビューモードを終了
     *
     * @return void
     */
    public function endPreviewMode();
}
