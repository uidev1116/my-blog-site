<?php

namespace Acms\Services\Login;

use Acms\Services\Facades\Common;
use Acms\Services\Login\Exceptions\BadRequestException;
use Acms\Services\Login\Exceptions\ExpiredException;

class Helper
{
    /**
     * @param array $context
     * @param int $lifetime
     * @return string
     */
    public function createTimedLinkParams($context, $lifetime)
    {
        $salt = Common::genPass(32); // 事前共有鍵
        $context['expire'] = REQUEST_TIME + $lifetime; // 有効期限
        $context = acmsSerialize($context);
        $prk = hash_hmac('sha256', PASSWORD_SALT_1, $salt);
        $derivedKey = hash_hmac('sha256', $prk, $context);
        $params = http_build_query(array(
            'key' => $derivedKey,
            'salt' => $salt,
            'context' => $context,
        ));
        return $params;
    }

    /**
     * @param string $key
     * @param string $salt
     * @param string $context
     * @return array
     * @throws BadRequestException
     * @throws ExpiredException
     */
    public function validateTimedLinkParams($key, $salt, $context)
    {
        $prk = hash_hmac('sha256', PASSWORD_SALT_1, $salt);
        $derivedKey = hash_hmac('sha256', $prk, $context);
        if (!hash_equals($key, $derivedKey)) {
            throw new BadRequestException('Bad request.');
        }
        $context = acmsUnserialize($context);
        if (!isset($context['expire'])) {
            throw new BadRequestException('Bad request.');
        }
        if (REQUEST_TIME > $context['expire']) {
            throw new ExpiredException('Expired.');
        }
        return $context;
    }
}
