<?php

namespace Acms\Services\Login\Traits;

use Acms\Services\Login\Exceptions\BadRequestException;
use Acms\Services\Login\Exceptions\ExpiredException;
use DB;
use SQL;

/**
 * メール認証のURLを検証するための機能
 */
trait ValidateAuthUrl
{
    /**
     * トークンのキーを削除要に保持
     *
     * @var string
     */
    protected $key;

    /**
     * トークンのタイプを削除用に保持
     *
     * @var string
     */
    protected $type;

    /**
     * トークンのキーを取得
     *
     * @param array $data
     * @return string
     */
    abstract protected function getTokenKey(array $data): string;

    /**
     * トークンのタイプを取得
     *
     * @return string
     */
    abstract protected function getTokenType(): string;

    /**
     * 現在のURLが認証URLかチェック
     *
     * @return bool
     */
    protected function isAuthUrl(): bool
    {
        $key = $this->Get->get('key');
        $salt = $this->Get->get('salt');
        $context = $this->Get->get('context');

        if (empty($key) || empty($salt) || empty($context)) {
            return false;
        }
        return true;
    }

    /**
     * 認証URLを検証
     *
     * @return array
     * @throws BadRequestException
     * @throws ExpiredException
     */
    protected function validateAuthUrl(): array
    {
        $key = $this->Get->get('key');
        $salt = $this->Get->get('salt');
        $context = $this->Get->get('context');

        $data = $this->validateUrl($key, $salt, $context);
        if (!isset($data['token'])) {
            throw new BadRequestException('Bad request.');
        }
        if (!$this->validateToken($data['token'], $data)) {
            throw new BadRequestException('Bad request.');
        }
        return $data;
    }

    /**
     * 認証URLのトークンと保存してあるトークンを比較
     *
     * @param string $token
     * @param array $data
     * @return bool
     */
    protected function validateToken(string $token, array $data): bool
    {
        $this->key = $this->getTokenKey($data);
        $this->type = $this->getTokenType();

        if (empty($this->key) || empty($this->type)) {
            return false;
        }
        $sql = SQL::newSelect('token');
        $sql->setSelect('token_value');
        $sql->addWhereOpr('token_key', $this->key);
        $sql->addWhereOpr('token_type', $this->type);
        $sql->addWhereOpr('token_value', $token);
        $sql->addWhereOpr('token_expire', date('Y-m-d H:i:s', REQUEST_TIME), '>');
        $t = DB::query($sql->get(dsn()), 'one');

        return $t === $token;
    }

    /**
     * 使用したトークンを削除
     *
     * @return void
     */
    protected function removeToken(): void
    {
        if (empty($this->key) || empty($this->type)) {
            return;
        }
        // 使用済みのトークンを削除
        $sql = SQL::newDelete('token');
        $sql->addWhereOpr('token_key', $this->key);
        $sql->addWhereOpr('token_type', $this->type);
        DB::query($sql->get(dsn()), 'exec');

        // 有効期限切れのトークンを削除
        $sql = SQL::newDelete('token');
        $sql->addWhereOpr('token_expire', date('Y-m-d H:i:s', REQUEST_TIME), '<');
        DB::query($sql->get(dsn()), 'exec');
    }

    /**
     * 認証URlを検証
     *
     * @param string $key
     * @param string $salt
     * @param string $context
     * @return array
     * @throws BadRequestException
     * @throws ExpiredException
     */
    protected function validateUrl(string $key, string $salt, string $context): array
    {
        if (empty($key) || empty($salt) || empty($context)) {
            throw new BadRequestException('Bad request.');
        }
        $prk = hash_hmac('sha256', PASSWORD_SALT_1, $salt);
        $derivedKey = hash_hmac('sha256', $prk, $context);
        if (!hash_equals($key, $derivedKey)) {
            throw new BadRequestException('Bad request.');
        }
        $data = acmsUnserialize($context);
        if (!isset($data['expire'])) {
            throw new BadRequestException('Bad request.');
        }
        if (REQUEST_TIME > $data['expire']) {
            throw new ExpiredException('Expired.');
        }
        return $data;
    }
}
