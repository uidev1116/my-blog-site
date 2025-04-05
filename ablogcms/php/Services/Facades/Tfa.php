<?php

namespace Acms\Services\Facades;

/**
 * @method static bool isAvailable() 2段階認証が有効か判定
 * @method static string createSecret() 秘密鍵を作成
 * @method static string getSecretForQRCode(string $secret, string $label) 秘密鍵のQRコード画像を取得
 * @method static string getSecretForManual(string $secret) 秘密鍵の手動入力用文字列を取得
 * @method static bool verifyCode(string $secret, string $code) コードを検証
 * @method static bool checkCorrectTime() サーバー時間が正しいかチェック
 * @method static string|false getSecretKey(int $uid) 秘密鍵を取得
 * @method static bool isAvailableAccount(int $uid) 2段階認証が有効なアカウントか判定
 * @method static bool verifyAccount(int $uid, string $code) アカウントを検証
 * @method static bool checkAuthority() 権限をチェック
 */
class Tfa extends Facade
{
    protected static $instance;

    /**
     * @return string
     */
    protected static function getServiceAlias()
    {
        return 'login.tfa';
    }

    /**
     * @return bool
     */
    protected static function isCache()
    {
        return true;
    }
}
