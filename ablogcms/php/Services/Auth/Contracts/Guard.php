<?php

namespace Acms\Services\Auth\Contracts;

interface Guard
{
    /**
     * 指定ユーザーが読者か
     *
     * @param int|null $uid
     * @return bool
     */
    public function isSubscriber($uid=SUID);

    /**
     * 指定したユーザーが投稿者か
     *
     * @param int|null $uid
     * @return bool
     */
    public function isContributor($uid=SUID);

    /**
     * 指定したユーザーが変種者か
     *
     * @param int|null $uid
     * @return bool
     */
    public function isEditor($uid=SUID);

    /**
     * 指定したユーザーが管理者か
     *
     * @param int|null $uid
     * @return bool
     */
    public function isAdministrator($uid=SUID);

    /**
     *ログイン中のユーザーがそのブログにおいて読者以上の権限があるか
     *
     * @param int|null $bid
     * @return bool
     */
    public function isPermissionOfSubscriber($bid=BID);

    /**
     * ログイン中のユーザーがそのブログにおいて投稿者以上の権限があるか
     *
     * @param int|null $bid
     * @return bool
     */
    public function isPermissionOfContributor( $bid=BID);

    /**
     * ログイン中のユーザーがそのブログにおいて編集者以上の権限があるか
     *
     * @param int|null $bid
     * @return bool
     */
    public function isPermissionOfEditor($bid=BID);

    /**
     * ログイン中のユーザーがそのブログにおいて管理者以上の権限があるか
     *
     * @param int|null $bid
     * @return bool
     */
    public function isPermissionOfAdministrator($bid=BID);

    /**
     * 指定したユーザーがSNSログインを利用できるか
     *
     * @param int|null $uid
     * @param int|null $uid
     * @return bool
     */
    public function isPermissionOfSnsLogin($uid=SUID, $bid=BID);

    /**
     * ログインしているユーザーが特定の管理ページで権限があるかチェック
     *
     * @param array{
     *  bid?: int,
     *  cid?: int,
     *  rid?: int,
     *  mid?: int,
     *  scid?: int,
     *  setid?: int
     * } $ids
     *
     * @return bool
     */
    public function checkShortcut(array $ids);

    /**
     * 指定したユーザーの権限があるブログリストを取得
     *
     * @param int $uid
     * @return array
     */
    public function getAuthorizedBlog($uid);
}
