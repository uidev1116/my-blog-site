<?php

namespace Acms\Services\Login;

use RobThree\Auth\TwoFactorAuth;
use DB;
use SQL;
use Common;

class Tfa
{
    /**
     * @var \RobThree\Auth\TwoFactorAuth;
     */
    protected $tfa;

    /**
     * Constructor
     */
    public function __construct($appName)
    {
        $this->tfa = new TwoFactorAuth($appName);
    }

    /**
     * @return bool
     */
    public function isAvailable()
    {
        if (config('two_factor_auth') !== 'on') {
            return false;
        }
        return true;
    }

    /**
     * 秘密鍵を作成
     *
     * @return string
     */
    public function createSecret()
    {
        return $this->tfa->createSecret(160);
    }

    /**
     * 秘密鍵のQRコード画像を取得
     *
     * @param string $secret 秘密鍵
     * @param string $label ラベル
     * @return string data:image
     */
    public function getSecretForQRCode($secret, $label)
    {
        return $this->tfa->getQRCodeImageAsDataUri($label, $secret);
    }

    /**
     * 秘密鍵を表示
     *
     * @param string $secret 秘密鍵
     * @return string
     */
    public function getSecretForManual($secret)
    {
        return chunk_split($secret, 4, ' ');
    }

    /**
     * 一時トークンが正しいかチェック
     *
     * @param string $secret 秘密鍵
     * @param string $code 一時トークン
     * @return boolean
     */
    public function verifyCode($secret, $code)
    {
        return $this->tfa->verifyCode($secret, $code);
    }

    /**
     * サーバー時間が正しいかチェック
     *
     * @return bool
     */
    public function checkCorrectTime()
    {
        try {
            $this->tfa->ensureCorrectTime();
        } catch (\RobThree\Auth\TwoFactorAuthException $e) {
            return false;
        }
        return true;
    }

    /**
     * @param int $uid
     * @return string|false
     */
    public function getSecretKey($uid)
    {
        $sql = SQL::newSelect('user');
        $sql->addSelect('user_tfa_secret_iv');
        $sql->addWhereOpr('user_id', $uid);
        $iv = DB::query($sql->get(dsn()), 'one');
        if (empty($iv)) {
            return false;
        }
        $sql = SQL::newSelect('user');
        $sql->addSelect('user_tfa_secret');
        $sql->addWhereOpr('user_id', $uid);
        $txt = DB::query($sql->get(dsn()), 'one');

        if (empty($txt)) {
            return false;
        }
        return Common::decrypt($txt, base64_decode($iv)); // @phpstan-ignore-line
    }

    /**
     * @param int $uid
     * @return bool
     */
    public function isAvailableAccount($uid)
    {
        if (!$this->isAvailable()) {
            return false;
        }
        $secret = $this->getSecretKey($uid);
        if (empty($secret)) {
            return false;
        }
        return true;
    }

    /**
     * @param int $uid
     * @param string $code
     * @return bool
     */
    public function verifyAccount($uid, $code)
    {
        $secret = $this->getSecretKey($uid);

        return $this->verifyCode($secret, $code);
    }

    /**
     * @return bool
     */
    public function checkAuthority()
    {
        // ２段階認証機能が有効でない
        if (!$this->isAvailable()) {
            return false;
        }
        // ログインしていない OR ユーザページでない
        if (!UID || !SUID) {
            return false;
        }
        // 自分自身でない（ただしスーパーユーザーは除く）
        if (!(RBID === SBID && sessionWithAdministration(BID)) && UID !== SUID) {
            return false;
        }
        return true;
    }
}
