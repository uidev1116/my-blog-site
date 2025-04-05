<?php

namespace Acms\Services\Common;

use Acms\Services\Facades\Database as DB;
use Acms\Services\Facades\Storage;
use Acms\Services\Facades\Entry;
use Acms\Services\Facades\Image;
use Acms\Services\Facades\Cache;
use Acms\Services\Facades\Media;
use Acms\Services\Facades\Logger as AcmsLogger;
use Acms\Services\Facades\RichEditor;
use Acms\Services\Facades\Application;
use Acms\Services\Facades\Session;
use phpseclib\Crypt\AES;
use phpseclib\Crypt\Random;
use cebe\markdown\MarkdownExtra;
use SQL;
use Tpl;
use Field;
use Field_Search;
use Field_Validation;
use Template;
use ACMS_Http;
use ACMS_Corrector;
use ACMS_POST_Image;
use ACMS_RAM;
use ACMS_Hook;
use Exception;
use RuntimeException;

class Helper
{
    /**
     * @var \Field
     */
    protected $Post;

    /**
     * @var \Field
     */
    protected $Get;

    /**
     * @var \Field
     */
    protected $Q;

    /**
     * extract()後の削除フィールドを一時保存
     *
     * @var \Field
     */
    protected $deleteField;

    /**
     * @var \Acms\Services\Cache\Contracts\AdapterInterface
     */
    protected $cacheField;

    /**
     * 現在のソルト
     *
     * @var string|null
     */
    private $currentSalt = null;

    /**
     * 1つ前のソルト
     *
     * @var string|null
     */
    private $previousSalt = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $app = \App::getInstance();
        assert($app instanceof \Acms\Application);
        $this->Q =& $app->getQueryParameter();
        $this->Get =& $app->getGetParameter();
        $this->Post =& $app->getPostParameter();
        $this->cacheField = Cache::field();
    }

    /**
     * @return string
     */
    public function getEncryptIv()
    {
        $cipher = new AES();
        $cipher->setKey(PASSWORD_SALT_1);

        return Random::string(($cipher->getBlockLength() >> 3));
    }

    /**
     * @param string $string
     * @param string $iv
     * @return string
     */
    public function encrypt($string, $iv)
    {
        $cipher = new AES();
        $cipher->setKey(PASSWORD_SALT_1);
        $cipher->setIV($iv);

        return base64_encode($cipher->encrypt($string));
    }

    /**
     * @param string $cipherText
     * @param string $iv
     * @return string
     */
    public function decrypt($cipherText, $iv)
    {
        $cipher = new AES();
        $cipher->setKey(PASSWORD_SALT_1);
        $cipher->setIV($iv);

        return $cipher->decrypt(base64_decode($cipherText)); // @phpstan-ignore-line
    }

    /**
     * アプリ全体で使用するSaltを更新・設定
     *
     * @return void
     */
    public function setAppSalt(): void
    {
        $sql = SQL::newSelect('sequence');
        $item = DB::query($sql->get(dsn()), 'row');

        if (!$item || !array_key_exists('sequence_current_salt', $item) || !array_key_exists('sequence_previous_salt', $item)) {
            $this->currentSalt = PASSWORD_SALT_1;
            $this->previousSalt = PASSWORD_SALT_2;
            AcmsLogger::error('データベースがアップデートされていません。管理画面の更新メニューからDBをアップデートください。');
            return;
        }
        $currentSalt = $item['sequence_current_salt'] ?? null;
        $previousSalt = $item['sequence_previous_salt'] ?? null;
        $updatedAt = strtotime($item['sequence_salt_updated_at'] ?? '2000-01-01 00:00:00');

        if ($currentSalt === null || $previousSalt === null) {
            $currentSalt = "base64:" . base64_encode(random_bytes(32));
            $previousSalt = "base64:" . base64_encode(random_bytes(32));
            $sql = SQL::newUpdate('sequence');
            $sql->addUpdate('sequence_current_salt', $currentSalt);
            $sql->addUpdate('sequence_previous_salt', $previousSalt);
            $sql->addUpdate('sequence_salt_updated_at', date('Y-m-d H:i:s', REQUEST_TIME));
            DB::query($sql->get(dsn()), 'exec');
        } elseif ((REQUEST_TIME - $updatedAt) > (60 * 60 * 24)) {
            $previousSalt = $currentSalt;
            $currentSalt = "base64:" . base64_encode(random_bytes(32));
            $sql = SQL::newUpdate('sequence');
            $sql->addUpdate('sequence_current_salt', $currentSalt);
            $sql->addUpdate('sequence_previous_salt', $previousSalt);
            $sql->addUpdate('sequence_salt_updated_at', date('Y-m-d H:i:s', REQUEST_TIME));
            DB::query($sql->get(dsn()), 'exec');
        }
        $this->currentSalt = $currentSalt;
        $this->previousSalt = $previousSalt;
    }

    /**
     * 現在のソルトを取得
     *
     * @return string
     */
    public function getCurrentSalt(): string
    {
        return $this->currentSalt ??  base64_encode(random_bytes(32));
    }

    /**
     * 1つ前のソルトを取得
     *
     * @return string
     */
    public function getPreviousSalt(): string
    {
        return $this->previousSalt ??  base64_encode(random_bytes(32));
    }

    /**
     * マークダウン文字列を解析する
     * @param string $txt
     * @return string
     */
    public function parseMarkdown($txt)
    {
        static $parser = null;
        if ($parser === null) {
            $parser = new MarkdownExtra();
        }
        return $parser->parse($txt);
    }

    /**
     * すぐにリダイレクトし、同一プロセスのバックグラウンドで処理を実行
     *
     * @param string $url
     */
    public function backgroundRedirect($url)
    {
        ignore_user_abort(true);
        set_time_limit(0);
        session_write_close(); // セッションロックを解除する

        $out = '';
        while (ob_get_level()) {
            ob_end_clean();
        }
        for ($i = 0; $i < 99999; $i++) {
            $out .= ' ';
        }

        header("HTTP/1.1 301");
        header("Content-Length: " . strlen($out));
        header("Connection: close");
        header("Location: " . $url);

        if (ob_get_level() === 0) {
            ob_start();
        }
        echo $out;
        sleep(2);
        ob_flush();
        flush();
        ob_end_flush();
    }

    /**
     * セキュリティヘッダー
     */
    public function addSecurityHeader()
    {
        // クリックジャッキング対策
        if (config('x_frame_options') !== 'off') {
            if (config('x_frame_options') === 'DENY') {
                header('X-FRAME-OPTIONS: DENY');
            } else {
                header('X-FRAME-OPTIONS: SAMEORIGIN');
            }
        }
        // X-XSS-Protection
        if (config('x_xss_protection') !== 'off') {
            header('X-XSS-Protection: 1; mode=block');
        }
        // X-Content-Type-Options
        if (config('x_content_type_options') !== 'off') {
            header('X-Content-Type-Options: nosniff');
        }
        // Strict-Transport-Security(HSTS)
        if (SSL_ENABLE && FULLTIME_SSL_ENABLE && config('strict_transport_security') !== 'off') {
            header('Strict-Transport-Security: ' . config('strict_transport_security', 'max-age=86400; includeSubDomains'));
        }
        // Content-Security-Policy
        $csp = config('content_security_policy');
        if (!empty($csp) && $csp !== 'off') {
            header('Content-Security-Policy: ' . $csp);
        }
        // Referrer-Policy
        $referrerPolicy = config('referrer_policy', 'strict-origin-when-cross-origin');
        if (
            in_array(
                $referrerPolicy,
                [
                    'no-referrer',
                    'no-referrer-when-downgrade',
                    'origin',
                    'origin-when-cross-origin',
                    'same-origin',
                    'strict-origin',
                    'strict-origin-when-cross-origin',
                    'unsafe-url'
                ],
                true
            )
        ) {
            header('Referrer-Policy: ' . $referrerPolicy);
        }
    }

    /**
     * CSRFトークンを生成
     *
     * @return string
     */
    public function createCsrfToken(): string
    {
        $session = Session::handle();
        if ($session->get('formTokenExpireAt') && $session->get('formTokenExpireAt') < REQUEST_TIME) {
            $session->delete('formToken'); // 更新期限がきれたCSRFトークンを削除
        }
        $token = $session->get('formToken');
        if (empty($token)) {
            $token = uniqueString();
            $session->set('formToken', $token);
        }
        $session->set('formTokenExpireAt', (REQUEST_TIME + (60 * 60 * 6))); // CSRFトークンを更新間隔を6時間に設定
        $session->save();

        return $token;
    }

    /**
     * CSRFトークンをFromに付与
     *
     * @param string $tpl
     * @return string
     */
    public function addCsrfToken($tpl)
    {
        $tpl = preg_replace('@(<input\s+type="hidden"\s+name="formUniqueToken"\s+value="[^"]+">)@i', '', $tpl);
        $tpl = preg_replace('@(<input\s+type="hidden"\s+name="formToken"\s+value="[^"]+">)@i', '', $tpl);
        $tpl = preg_replace('@(<meta\\s+name="csrf-token"\s+content="[^"]+">)@i', '', $tpl);

        // ログアウト時 && POSTリクエストではない && ログインページでない && フォームじゃない && コメントフォームじゃない 時 は session start しない（Set-Cookie しない）CDNなどのキャッシュのため
        if (
            1
            && !ACMS_SID
            && !ACMS_POST
            && !IS_AUTH_SYSTEM_PAGE
            && !defined('IS_OTHER_LOGIN')
            && strpos($tpl, 'ACMS_POST_Form_') === false
            && strpos($tpl, 'ACMS_POST_Comment_') === false
            && strpos($tpl, 'ACMS_POST_Shop') === false
            && strpos($tpl, 'ACMS_POST_2GET_Ajax') === false
            && strpos($tpl, 'check-csrf-token') === false
            && ACMS_RAM::blogStatus(BID) !== 'secret'
            && (!CID || ACMS_RAM::categoryStatus(CID) !== 'secret')
        ) {
            $token = uniqueString();
        } else {
            $token = $this->createCsrfToken();
        }

        // form unique token の埋め込み
        $tpl = preg_replace('@(?=<\s*/\s*form[^\w]*>)@i', '<input type="hidden" name="formUniqueToken" value="' . uniqueString() . '">' . "\n", $tpl);
        // form に token の埋め込み
        $tpl = preg_replace('@(?=<\s*/\s*form[^\w]*>)@i', '<input type="hidden" name="formToken" value="' . $token . '">' . "\n", $tpl);
        // meta に token の埋め込み
        $tpl = preg_replace('@(?=<\s*/\s*head[^\w]*>)@i', '<meta name="csrf-token" content="' . $token . '">', $tpl);

        return $tpl;
    }

    /**
     * CSRFトークンの存在チェック
     *
     * @return boolean
     */
    public function csrfTokenExists(): bool
    {
        $session = Session::handle();
        return !!$session->get('formToken');
    }

    /**
     * CSRFトークンのチェック
     *
     * @param string $token
     * @return boolean
     */
    public function checkCsrfToken(string $token): bool
    {
        $session = Session::handle();
        if (!!$session->get('formToken') && $session->get('formToken') === $token) {
            return true;
        }
        return false;
    }

    /**
     * ToDo: リファクタリング
     *
     * @param string $name
     * @return string
     */
    public function getHttpHeader(string $name): string
    {
        return $_SERVER[$name] ?? '';
    }

    /**
     * 許可されたajaxアクセスか判定（どのようなtpl指定であっても許可する）
     *
     * level-0: チェックをしない
     * level-1: RefererとAjaxリクエスト判定
     * level-2: CSRFトークンチェック
     *
     * @param int $level
     * @return bool
     */
    public function isAuthorizedAjaxRequest(int $level = 1): bool
    {
        try {
            if ($level === 0) {
                return true; // チェックを全くしない
            }
            if (!is_ajax()) {
                return false; // Ajaxアクセスでない場合
            }
            if (isCSRF()) {
                return false; // Refererが不正な場合
            }
            if ($level <= 1) {
                return true;
            }
            if (!$this->csrfTokenExists()) {
                return false; // CSRFトークンが存在しない
            }
            $token = $this->getHttpHeader('HTTP_X_CSRF_TOKEN');
            if (!$this->checkCsrfToken($token)) {
                return false; // CSRFトークンが一致しない
            }
            return true;
        } catch (Exception $e) {
        }
        return false;
    }

    /**
     * 管理画面でテンプレート直で書かれているパスを、エイリアスを含んだURLに修正
     *
     * @param string $txt
     * @return string
     */
    public function fixAliasPath($txt)
    {
        $regex  = '@' .
            '<\s*a(?:"[^"]*"|\'[^\']*\'|[^\'">])*href\s*=\s*("[^"]+"|\'[^\']+\'|[^\'"\s>]+)(?:"[^"]*"|\'[^\']*\'|[^\'">])*>|' .
            '<\s*form(?:"[^"]*"|\'[^\']*\'|[^\'">])*action\s*=\s*("[^"]+"|\'[^\']+\'|[^\'"\s>]+)(?:"[^"]*"|\'[^\']*\'|[^\'">])*>' .
            '@';
        $offset = 0;
        while (preg_match($regex, $txt, $match, PREG_OFFSET_CAPTURE, $offset)) {
            $offset = $match[0][1] + strlen($match[0][0]);
            for ($mpt = 1; $mpt <= 2; $mpt++) {
                if (!empty($match[$mpt][0])) {
                    break;
                }
            }

            $path = trim($match[$mpt][0], '\'"'); // @phpstan-ignore-line
            if (preg_match('/^(?=.*bid\/\d+\/)(?!.*aid\/\d+\/).*$/', $path, $pathMatch)) {
                $path = preg_replace('/bid\/(\d+)\//', 'bid/$1/aid/' . AID . '/', $path);
                $txt = substr_replace($txt, '"' . $path . '"', $match[$mpt][1], strlen($match[$mpt][0])); // @phpstan-ignore-line
            }
        }
        return $txt;
    }

    /**
     * extract()後の削除フィールドを取得
     *
     * @return \Field
     */
    public function getDeleteField()
    {
        return $this->deleteField;
    }

    /**
     * メールテンプレートの解決
     *
     * @param string $path
     * @param Field $field
     * @param string|null $charset
     *
     * @return string
     */
    public function getMailTxt($path, $field, $charset = null)
    {
        if (empty($path)) {
            return '';
        }
        try {
            $tpl = Storage::get($path, THEMES_DIR);
            $charset = detectEncode($tpl);
            $tpl = mb_convert_encoding($tpl, 'UTF-8', $charset);
            return $this->getMailTxtFromTxt($tpl, $field);
        } catch (\Exception $e) {
            AcmsLogger::warning('メールテンプレートを取得できませんでした', [
                'detaile' => $e->getMessage(),
                'path' => $path,
            ]);
            return '';
        }
    }

    /**
     * @param string $txt
     * @param Field $field
     * @return string
     */
    public function getMailTxtFromTxt($txt, $field)
    {
        try {
            global $extend_section_stack;
            $extend_section_stack = [];
            $txt = buildVarBlocks($txt, true);
            $txt = spreadTemplate(setGlobalVars($txt));
            if (isTemplateCacheEnabled()) {
                $txt = setGlobalVars($txt);
            }
            $tpl = build($txt, Field_Validation::singleton('post'), true);
            $extend_section_stack = [];

            $Tpl = new Template($tpl, new ACMS_Corrector());
            $vars = Tpl::buildField($field, $Tpl);
            $Tpl->add(null, $vars);
            return buildVarBlocks(buildIF($Tpl->get()));
        } catch (\Exception $e) {
            AcmsLogger::warning('メールテンプレートを組み立てできませんでした', [
                'detaile' => $e->getMessage(),
                'text' => $txt,
            ]);
            return '';
        }
    }

    /**
     * メール設定の取得
     *
     * @param array{
     *   smtp-host?: string,
     *   smtp-port?: string,
     *   smtp-user?: string,
     *   smtp-pass?: string,
     *   mail_from?: string,
     *   sendmail_path?: string,
     *   additional_headers?: string,
     *   smtp-google?: string,
     *   smtp-google-user?: string
     * } $argConfig
     *
     * @return non-empty-array<'additional_headers'|'mail_from'|'sendmail_path'|'smtp-google'|'smtp-google-user'|'smtp-host'|'smtp-pass'|'smtp-port'|'smtp-user',
     *   string
     * >
     */
    public function mailConfig($argConfig = [])
    {
        $config = [];

        foreach (
            [
                'mail_smtp-host' => 'smtp-host',
                'mail_smtp-port' => 'smtp-port',
                'mail_smtp-user' => 'smtp-user',
                'mail_smtp-pass' => 'smtp-pass',
                'mail_from' => 'mail_from',
                'mail_sendmail_path' => 'sendmail_path',
                'mail_google_smtp' => 'smtp-google',
                'mail_google_smtp_adrress' => 'smtp-google-user',
            ] as $cmsConfigKey => $mailConfigKey
        ) {
            $config[$mailConfigKey] = config($cmsConfigKey, '');
        }
        if (defined('LICENSE_OPTION_OEM') && LICENSE_OPTION_OEM) {
            $config['additional_headers'] = 'X-Mailer: ' . LICENSE_OPTION_OEM;
        } else {
            $config['additional_headers'] = 'X-Mailer: a-blog cms';
        }
        $config['sendmail_path'] = (string)ini_get('sendmail_path');

        if (config('mail_additional_headers')) {
            $config['additional_headers']   .= "\x0D\x0A" . config('mail_additional_headers');
        }
        return $argConfig + $config;
    }

    /**
     * パスワードジェネレータ
     *
     * @param int $len パスワードの長さ
     *
     * @return string
     */
    public function genPass($len)
    {
        $pass = '';
        for ($i = 0; $i < $len; $i++) {
            switch (rand(0, 5)) {
                case 0: // 0-9
                    if (!$i) {
                        $pass .= chr(rand(48, 57));
                        break;
                    }
                    $pass .= chr(rand(97, 122));
                    break;
                case 1: // A-Z
                case 2:
                    $pass .= chr(rand(65, 90));
                    break;
                default: // a-z
                    $pass .= chr(rand(97, 122));
            }
        }
        return $pass;
    }

    /**
     * タグの配列化
     *
     * @param $string
     * @param bool $checkReserved
     * @return string[]
     */
    public function getTagsFromString($string, $checkReserved = true)
    {
        $tags = preg_split(TAG_SEPARATER, $string, -1, PREG_SPLIT_NO_EMPTY);
        $tags = array_map('trim', $tags);
        $tags = array_unique($tags);
        if ($checkReserved) {
            $tags = array_filter($tags, function ($tag) {
                return !isReserved($tag);
            });
        }
        return $tags;
    }

    /**
     * エントリーのフルテキストを取得
     *
     * @param int $eid
     *
     * @return string
     */
    public function loadEntryFulltext($eid)
    {
        $unitRepository = Application::make('unit-repository');
        assert($unitRepository instanceof \Acms\Services\Unit\Repository);

        $text = $unitRepository->getUnitSearchText($eid);
        $entry = [
            'id' => $eid,
            'title' => ACMS_RAM::entryTitle($eid),
            'code' => ACMS_RAM::entryCode($eid),
        ];

        $sql = SQL::newSelect('field');
        $sql->addSelect('field_key');
        $sql->addSelect('field_value');
        $sql->addWhereOpr('field_search', 'on');
        $sql->addWhereOpr('field_eid', $eid);
        $q = $sql->get(dsn());

        $field = [];
        if (DB::query($q, 'fetch') && ($row = DB::fetch($q))) {
            do {
                if (!isset($field[$row['field_key']])) {
                    $field[$row['field_key']] = [];
                }
                $field[$row['field_key']][] = $row['field_value'];
            } while ($row = DB::fetch($q));
        }

        if (HOOK_ENABLE) {
            $hook = ACMS_Hook::singleton();
            $hook->call('filterEntryFulltext', [&$entry, &$field, $eid]);
        }

        $metaText = implode(
            ' ',
            [
                implode(' ', array_values($entry)),
                implode(' ', array_map(function (array $values) {
                    return implode(' ', $values);
                }, $field)),
            ]
        );

        $text = fulltextUnitData($text);
        $fulltext = preg_replace('/\s+/u', ' ', strip_tags($text))
        . "\x0d\x0a\x0a\x0d" . preg_replace('/\s+/u', ' ', strip_tags($metaText))
            ;
        return $fulltext;
    }

    /**
     * ユーザーのフルテキストを取得
     *
     * @param int $uid
     *
     * @return string
     */
    public function loadUserFulltext($uid)
    {
        $user = [
            'name' => ACMS_RAM::userName($uid),
            'code' => ACMS_RAM::userCode($uid),
            'mail' => ACMS_RAM::userMail($uid),
            'mail_mobile' => ACMS_RAM::userMailMobile($uid),
            'url' => ACMS_RAM::userUrl($uid),
        ];

        $SQL = SQL::newSelect('field');
        $SQL->addSelect('field_key');
        $SQL->addSelect('field_value');
        $SQL->addWhereOpr('field_search', 'on');
        $SQL->addWhereOpr('field_uid', $uid);
        $q = $SQL->get(dsn());

        $field = [];
        if (DB::query($q, 'fetch') && ($row = DB::fetch($q))) {
            do {
                if (!isset($field[$row['field_key']])) {
                    $field[$row['field_key']] = [];
                }
                $field[$row['field_key']][] = $row['field_value'];
            } while ($row = DB::fetch($q));
        }

        if (HOOK_ENABLE) {
            $hook = ACMS_Hook::singleton();
            $hook->call('filterUserFulltext', [&$user, &$field, $uid]);
        }

        $userText = implode(' ', array_values($user));
        $metaText = implode(' ', array_map(function (array $values) {
            return implode(' ', $values);
        }, $field));

        $fulltext = preg_replace('/\s+/u', ' ', strip_tags($userText))
        . "\x0d\x0a\x0a\x0d" . preg_replace('/\s+/u', ' ', strip_tags($metaText))
            ;
        return $fulltext;
    }

    /**
     * カテゴリのフルテキストを取得
     *
     * @param int $cid
     *
     * @return string
     */
    public function loadCategoryFulltext($cid)
    {
        $category = [
            'name' => ACMS_RAM::categoryName($cid),
            'code' => ACMS_RAM::categoryCode($cid),
        ];

        $SQL = SQL::newSelect('field');
        $SQL->addSelect('field_key');
        $SQL->addSelect('field_value');
        $SQL->addWhereOpr('field_search', 'on');
        $SQL->addWhereOpr('field_cid', $cid);
        $q = $SQL->get(dsn());

        $field = [];
        if (DB::query($q, 'fetch') && ($row = DB::fetch($q))) {
            do {
                if (!isset($field[$row['field_key']])) {
                    $field[$row['field_key']] = [];
                }
                $field[$row['field_key']][] = $row['field_value'];
            } while ($row = DB::fetch($q));
        }

        if (HOOK_ENABLE) {
            $hook = ACMS_Hook::singleton();
            $hook->call('filterCategoryFulltext', [&$category, &$field, $cid]);
        }

        $categoryText = implode(' ', array_values($category));
        $metaText = implode(' ', array_map(function (array $values) {
            return implode(' ', $values);
        }, $field));

        $fulltext = preg_replace('/\s+/u', ' ', strip_tags($categoryText))
        . "\x0d\x0a\x0a\x0d" . preg_replace('/\s+/u', ' ', strip_tags($metaText));
        return $fulltext;
    }

    /**
     * ブログのフルテキストを取得
     *
     * @param int $bid
     *
     * @return string
     */
    public function loadBlogFulltext($bid)
    {
        $blog = [
            'name' => ACMS_RAM::blogName($bid),
            'code' => ACMS_RAM::blogCode($bid),
            'domain' => ACMS_RAM::blogDomain($bid),
        ];

        $SQL = SQL::newSelect('field');
        $SQL->addSelect('field_key');
        $SQL->addSelect('field_value');
        $SQL->addWhereOpr('field_search', 'on');
        $SQL->addWhereOpr('field_bid', $bid);
        $q = $SQL->get(dsn());

        $field = [];
        if (DB::query($q, 'fetch') && ($row = DB::fetch($q))) {
            do {
                if (!isset($field[$row['field_key']])) {
                    $field[$row['field_key']] = [];
                }
                $field[$row['field_key']][] = $row['field_value'];
            } while ($row = DB::fetch($q));
        }

        if (HOOK_ENABLE) {
            $hook = ACMS_Hook::singleton();
            $hook->call('filterBlogFulltext', [&$blog, &$field, $bid]);
        }

        $blogText = implode(' ', array_values($blog));
        $metaText = implode(' ', array_map(function (array $values) {
            return implode(' ', $values);
        }, $field));

        $fulltext = preg_replace('/\s+/u', ' ', strip_tags($blogText))
        . "\x0d\x0a\x0a\x0d" . preg_replace('/\s+/u', ' ', strip_tags($metaText));
        return $fulltext;
    }

    /**
     * フルテキストの保存
     *
     * @param string $type フルテキストのタイプ
     * @param int $id
     * @param string|null $fulltext
     * @param int $targetBid
     *
     * @return void
     */
    public function saveFulltext($type, $id, $fulltext = null, $targetBid = BID)
    {
        $DB = DB::singleton(dsn());
        $SQL = SQL::newDelete('fulltext');
        $SQL->addWhereOpr('fulltext_' . $type, $id);
        $DB->query($SQL->get(dsn()), 'exec');

        if (!empty($fulltext)) {
            $SQL    = SQL::newInsert('fulltext');
            $SQL->addInsert('fulltext_value', $fulltext);
            if (config('ngram')) {
                $SQL->addInsert(
                    'fulltext_ngram',
                    preg_replace('/(　|\s)+/u', ' ', join(' ', ngram(strip_tags($fulltext), config('ngram'))))
                );
            }
            $SQL->addInsert('fulltext_' . $type, $id);
            $SQL->addInsert('fulltext_blog_id', $targetBid);
            $DB->query($SQL->get(dsn()), 'exec');
        }
    }

    /**
     * ファイルダウンロード
     *
     * @param string $path
     * @param string $fileName
     * @param string|boolean $extension // 指定すると、Content-Disposition: inline になります。
     * @param boolean $remove
     * @return never
     */
    public function download($path, $fileName, $extension = false, $remove = false)
    {
        $fileNameEncode = urlencode($fileName);
        $size = filesize($path);
        if ($extension) {
            $inlineExtensions = configArray('media_inline_download_extension');
            $mime = false;
            $fp = fopen($path, "rb");

            foreach ($inlineExtensions as $i => $value) {
                if ($extension == $value) {
                    $mime = config('media_inline_download_mime', false, $i);
                }
            }
            header("Content-Disposition: inline; filename=\"$fileName\"; filename*=UTF-8''$fileNameEncode");
            if ($mime) {
                header("Content-Type: $mime");
            } else {
                header('Content-Type: application/octet-stream');
            }

            if (isset($_SERVER["HTTP_RANGE"]) && $_SERVER["HTTP_RANGE"]) {
                // 要求された開始位置と終了位置を取得
                list($start, $end) = sscanf($_SERVER["HTTP_RANGE"], "bytes=%d-%d");
                // 終了位置が指定されていない場合(適当に1000000bytesづつ出す)
                if (empty($end)) {
                    $end = $start + 1000000 - 1;
                }
                // 終了位置がファイルサイズを超えた場合
                if ($end >= ($size - 1)) {
                    $end = $size - 1;
                }
                // 部分コンテンツであることを伝える
                header("HTTP/1.1 206 Partial Content");
                // コンテンツ範囲を伝える
                header("Content-Range: bytes {$start}-{$end}/{$size}");
                // 実際に送信するコンテンツ長: 終了位置 - 開始位置 + 1
                $size = $end - $start + 1;
                // ファイルポインタを開始位置まで移動
                fseek($fp, $start);
            }
            header('Content-Length: ' . $size);

            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            if ($size) {
                echo fread($fp, $size);
            }
            fclose($fp);
        } else {
            header("Content-Disposition: attachment; filename=\"$fileName\"; filename*=UTF-8''$fileNameEncode");
            if (strpos(UA, 'MSIE')) {
                header('Content-Type: text/download');
            } else {
                header('Content-Type: application/octet-stream');
            }
            header('Content-Length: ' . $size);

            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            readfile($path);
        }
        if ($remove) {
            Storage::remove($path);
        }
        die();
    }

    /**
     * カスタムフィールドキャッシュの削除
     *
     * @param 'bid' | 'uid' | 'cid' | 'mid' | 'eid' $type
     * @param int $id
     * @param int|null $rvid
     */
    public function deleteFieldCache($type, $id, $rvid = null)
    {
        // キャッシュ削除
        if ($type) {
            $cacheBid = $type === 'bid' ? $id : '';
            $cacheUid = $type === 'uid' ? $id : '';
            $cacheCid = $type === 'cid' ? $id : '';
            $cacheMid = $type === 'mid' ? $id : '';
            $cacheEid = $type === 'eid' ? $id : '';
        }
        $cacheKey = "cache-field-bid_$cacheBid-uid_$cacheUid-cid_$cacheCid-mid_$cacheMid-eid_$cacheEid-rvid_$rvid-";

        $this->cacheField->forget($cacheKey . '0');
        $this->cacheField->forget($cacheKey . '1');
    }

    public function flushCache()
    {
        $this->cacheField->flush();
    }

    /**
     * カスタムフィールドの削除
     *
     * @param string $type
     * @param int $id
     * @param int|null $rvid
     * @param int|null $blogId
     * @return void
     */
    public function deleteField($type, $id, $rvid = null, $blogId = null)
    {
        $this->deleteFieldCache($type, $id, $rvid);

        if ($type === 'eid' && $rvid) {
            $sql = SQL::newDelete('field_rev');
            $sql->addWhereOpr('field_eid', $id);
            $sql->addWhereOpr('field_rev_id', $rvid);
            if ($blogId !== null) {
                $sql->addWhereOpr('field_blog_id', $blogId);
            }
            DB::query($sql->get(dsn()), 'exec');
        } else {
            $sql = SQL::newDelete('field');
            $sql->addWhereOpr('field_' . $type, $id);
            if ($blogId !== null) {
                $sql->addWhereOpr('field_blog_id', $blogId);
            }
            DB::query($sql->get(dsn()), 'exec');
        }
    }

    /**
     * ブログID, カテゴリーID, エントリーID，ユーザーIDの
     * いずれか指定されたカスタムフィールドをFieldオブジェクトで返す
     *
     * @param null|int $bid
     * @param null|int $uid
     * @param null|int $cid
     * @param null|int $eid
     * @return Field
     */
    public function loadField($bid = null, $uid = null, $cid = null, $mid = null, $eid = null, $rvid = null, $rewrite = false)
    {
        $cacheKey = "cache-field-bid_$bid-uid_$uid-cid_$cid-mid_$mid-eid_$eid-rvid_$rvid-";
        $cacheKey .= ($rewrite ? '1' : '0');

        $cacheItem = $this->cacheField->getItem($cacheKey);
        if ($cacheItem && $cacheItem->isHit()) {
            $cacheData = $cacheItem->get();
            if ($cacheData instanceof Field) {
                return $cacheData;
            }
            $this->cacheField->forget($cacheKey);
        }
        $Field = new Field();
        if (
            1
            && is_null($bid)
            && is_null($uid)
            && is_null($cid)
            && is_null($eid)
            && is_null($mid)
        ) {
            return $Field;
        }
        $DB = DB::singleton(dsn());
        if ($rvid && $eid) {
            $SQL    = SQL::newSelect('field_rev');
            $SQL->addWhereOpr('field_rev_id', $rvid);
        } else {
            $SQL    = SQL::newSelect('field');
        }
        $SQL->addSelect('field_key');
        $SQL->addSelect('field_value');
        $SQL->addSelect('field_search');
        if (!is_null($bid)) {
            $SQL->addWhereOpr('field_bid', $bid);
        }
        if (!is_null($uid)) {
            $SQL->addWhereOpr('field_uid', $uid);
        }
        if (!is_null($cid)) {
            $SQL->addWhereOpr('field_cid', $cid);
        }
        if (!is_null($eid)) {
            $SQL->addWhereOpr('field_eid', $eid);
        }
        if (!is_null($mid)) {
            $SQL->addWhereOpr('field_mid', $mid);
        }
        $SQL->setOrder('field_sort');
        $q  = $SQL->get(dsn());
        $DB->query($q, 'fetch');

        $mediaList = [];
        $mediaIds = [];
        $useMediaField = [];
        while ($row = $DB->fetch($q)) {
            $fixPaht    = '';
            $fd         = $row['field_key'];
            if (strpos($fd, '@media') !== false) {
                $fdSource = substr($fd, 0, -6);
                $mediaIds[] = intval($row['field_value']);
                $useMediaField[] = $fdSource;
            }
            $Field->addField($row['field_key'], $fixPaht . $row['field_value']);
            $Field->setMeta($row['field_key'], 'search', $row['field_search'] === 'on');
        }
        if ($mediaIds) {
            $DB = DB::singleton(dsn());
            $SQL = SQL::newSelect('media');
            $SQL->addWhereIn('media_id', $mediaIds);
            $q  = $SQL->get(dsn());
            $DB->query($q, 'fetch');
            while ($media = $DB->fetch($q)) {
                $mid = intval($media['media_id']);
                $mediaList[$mid] = $media;
            }
        }
        Media::injectMediaField($Field, $mediaList, $useMediaField);

        $cacheItem->set($Field);
        $this->cacheField->putItem($cacheItem);

        return $Field;
    }

    /**
     * カスタムフィールドの保存
     *
     * @param string $type
     * @param int $id
     * @param Field|null $Field
     * @param Field|null $deleteField
     * @param int|null $rvid
     * @param int $targetBid
     *
     * @return bool
     */
    public function saveField($type, $id, $Field = null, $deleteField = null, $rvid = null, $targetBid = BID)
    {
        if (empty($id)) {
            AcmsLogger::warning('idが空で、フィールドを保存できませんでした', [
                'type' => $type,
                'bid' => $targetBid,
            ]);
            return false;
        }

        $this->deleteFieldCache($type, $id, $rvid);

        $DB = DB::singleton(dsn());
        $ARCHIVES_DIR_TO = ARCHIVES_DIR;
        $tableName = 'field';
        $asNewVersion = false;

        if (
            1
            && enableRevision()
            && $rvid
            && $type == 'eid'
        ) {
            $tableName = 'field_rev';
            if (Entry::isNewVersion()) {
                $asNewVersion = true;
            }
        }

        $SQL    = SQL::newDelete($tableName);
        $SQL->addWhereOpr('field_' . $type, $id);
        if ($tableName  === 'field_rev') {
            $SQL->addWhereOpr('field_rev_id', $rvid);
        }
        if ($Field && $Field->get('updateField') === 'on') {
            $fkey   = [];
            $Field->delete('updateField');
            foreach ($Field->listFields() as $fd) {
                $fkey[] = $fd;
            }
            $SQL->addWhereIn('field_key', $fkey);
        }
        $DB->query($SQL->get(dsn()), 'exec');

        if (!empty($Field)) {
            foreach ($Field->listFields() as $fd) {
                // copy revision
                if ($asNewVersion) {
                    if (strpos($fd, '@path')) {
                        $list   = $Field->getArray($fd, true);
                        $base   = substr($fd, 0, (-1 * strlen('@path')));
                        $set    = false;
                        foreach ($list as $i => $val) {
                            $path = $val;
                            if (in_array($path, Entry::getUploadedFiles(), true)) {
                                continue;
                            }
                            if (!$set) {
                                $Field->delete($fd);
                                $Field->delete($base . '@largePath');
                                $Field->delete($base . '@tinyPath');
                                $Field->delete($base . '@squarePath');
                                $set = true;
                            }
                            if (Storage::isFile(ARCHIVES_DIR . $path)) {
                                $info       = pathinfo($path);
                                $dirname    = empty($info['dirname']) ? '' : $info['dirname'] . '/';
                                Storage::makeDirectory($ARCHIVES_DIR_TO . $dirname);
                                $ext        = empty($info['extension']) ? '' : '.' . $info['extension'];
                                $newPath    = $dirname . uniqueString() . $ext;

                                $path       = ARCHIVES_DIR . $path;
                                $largePath  = otherSizeImagePath($path, 'large');
                                $tinyPath   = otherSizeImagePath($path, 'tiny');
                                $squarePath = otherSizeImagePath($path, 'square');

                                $newLargePath   = otherSizeImagePath($newPath, 'large');
                                $newTinyPath    = otherSizeImagePath($newPath, 'tiny');
                                $newSquarePath  = otherSizeImagePath($newPath, 'square');

                                Storage::copy($path, $ARCHIVES_DIR_TO . $newPath);
                                Storage::copy($largePath, $ARCHIVES_DIR_TO . $newLargePath);
                                Storage::copy($tinyPath, $ARCHIVES_DIR_TO . $newTinyPath);
                                Storage::copy($squarePath, $ARCHIVES_DIR_TO . $newSquarePath);

                                if (!Storage::isReadable($newLargePath)) {
                                    $newLargePath = '';
                                }
                                if (!Storage::isReadable($newTinyPath)) {
                                    $newTinyPath = '';
                                }
                                if (!Storage::isReadable($newSquarePath)) {
                                    $newSquarePath = '';
                                }
                                $Field->add($fd, $newPath);
                                $Field->add($base . '@largePath', $newLargePath);
                                $Field->add($base . '@tinyPath', $newTinyPath);
                                $Field->add($base . '@squarePath', $newSquarePath);
                            } else {
                                $Field->add($fd, '');
                                $Field->add($base . '@largePath', '');
                                $Field->add($base . '@tinyPath', '');
                                $Field->add($base . '@squarePath', '');
                            }
                        }
                    }
                }

                foreach ($Field->getArray($fd, true) as $i => $val) {
                    $SQL    = SQL::newInsert($tableName);
                    $SQL->addInsert('field_key', $fd);
                    $SQL->addInsert('field_value', $val);
                    $SQL->addInsert('field_sort', $i + 1);
                    $SQL->addInsert('field_search', $Field->getMeta($fd, 'search') ? 'on' : 'off');
                    $SQL->addInsert('field_' . $type, $id);
                    $SQL->addInsert('field_blog_id', $targetBid);
                    if ($tableName  === 'field_rev') {
                        $SQL->addInsert('field_rev_id', $rvid);
                    }
                    $DB->query($SQL->get(dsn()), 'exec');
                }
            }
        }
        return true;
    }

    /**
     * URIオブジェクトの取得
     *
     * @param \Field $Post
     *
     * @return \Field
     */
    public function getUriObject($Post)
    {
        $Uri = new Field();

        //-----
        // arg
        if (!$aryFd = $Post->getArray('arg')) {
            $aryFd = array_diff($Post->listFields(), $Post->getArray('field'), $Post->getArray('query'));
        }
        foreach ($aryFd as $fd) {
            //---------
            // field
            if ('field' === $fd and $aryField = $Post->getArray('field')) {
                $Field = new Field_Search();
                foreach ($aryField as $field) {
                    $Field->set($field);
                    $Field->setConnector($field);
                    $Field->setOperator($field);
                    $aryValue       = $Post->getArray($field);
                    $aryConnector   = $Post->getArray($field . '@connector');
                    $aryOperator    = $Post->getArray($field . '@operator');
                    $Field->addSeparator($field, $Post->get($field . '@separator', 'and'));

                    if (!!($cnt = max(count($aryValue), count($aryConnector), count($aryOperator)))) {
                        $defaultConnector   = 'and';
                        $defaultOperator    = 'eq';
                        if (empty($aryConnector) && empty($aryOperator) /*and 2 <= count($aryValue)*/) {
                            $defaultConnector   = 'or';
                        }
                        if (!empty($aryConnector)) {
                            $defaultConnector   = $aryConnector[0];
                        }
                        if (!empty($aryOperator)) {
                            $defaultOperator    = $aryOperator[0];
                        }
                        for ($i = 0; $i < $cnt; $i++) {
                            $Field->add($field, isset($aryValue[$i]) ? $aryValue[$i] : '');
                            $Field->addConnector($field, isset($aryConnector[$i]) ? $aryConnector[$i] : $defaultConnector);
                            $Field->addOperator($field, isset($aryOperator[$i]) ? $aryOperator[$i] : $defaultOperator);
                        }
                    }
                }
                $Uri->addChild('field', $Field);

            //-------
            // query
            } elseif ('query' === $fd && $aryQuery = $Post->getArray('query')) {
                $Query  = new Field();
                foreach ($aryQuery as $query) {
                    $Query->set($query, $Post->getArray($query));
                }
                $Uri->addChild('query', $Query);

            //-------
            // value
            } else {
                $Uri->set($fd, $Post->getArray($fd));
            }
        }
        return $Uri;
    }

    /**
     * POSTデータからデータの抜き出し
     *
     * @param string $scp
     * @param \ACMS_Validator|null $V
     * @param \Field|null $deleteField
     * @return \Field_Validation
     */
    public function extract($scp = 'field', $V = null, $deleteField = null)
    {
        $Field = new Field_Validation();
        $this->deleteField = $deleteField;

        $ARCHIVES_DIR = ARCHIVES_DIR;

        if (!$this->deleteField) {
            $this->deleteField = new Field();
        }

        if ($takeover = $this->Post->get($scp . ':takeover')) {
            $Field->overload(acmsUnserialize($takeover));
            $this->Post->delete($scp . ':takeover');
        }

        $Field->overload($this->Post->dig($scp));
        $this->Post->addChild($scp, $Field);

        // 許可ファイル拡張子をまとめておく
        $allow_file_extensions = array_merge(
            configArray('file_extension_document'),
            configArray('file_extension_archive'),
            configArray('file_extension_movie'),
            configArray('file_extension_audio')
        );

        //-------
        // child
        foreach ($Field->listFields() as $fd) {
            if (!$this->Post->isExists($fd . ':field')) {
                continue;
            }
            $this->Post->set($fd, $Field->getArray($fd));
            $Field->delete($fd);
            $Field->addChild($fd, $this->extract($fd));
        }

        // アップロード処理中の画像・ファイルを保存する変数
        // アップロード処理中のファイルが誤って削除されることを防ぐために利用
        $processingMediaFiles = [];
        foreach ($this->Post->listFields() as $metaFd) {
            //-----------
            // converter
            if (
                1
                and preg_match('@^(.+)(?:\:c|\:converter)$@', $metaFd, $match)
                and $Field->isExists($match[1])
            ) {
                $fd = $match[1];
                $aryVal = [];
                foreach ($Field->getArray($fd) as $val) {
                    $aryVal[] = mb_convert_kana($val, $this->Post->get($metaFd), 'UTF-8');
                }
                $Field->setField($fd, $aryVal);
                $this->Post->delete($metaFd);
                continue;
            }
            //-----------
            // extension
            if (
                1
                and preg_match('@^(.+):extension$@', $metaFd, $match)
                and $Field->isExists($match[1])
            ) {
                $fd         = $match[1];
                $type       = $this->Post->get($fd . ':extension');
                $dataUrl    = false;
                $this->Post->delete($fd . ':extension');

                if ($type === 'media') {
                    foreach ($Field->getArray($fd) as $mediaValue) {
                        $Field->addField($fd . '@media', $mediaValue);
                    }
                } elseif ($type === 'paper-editor' || $type === 'rich-editor') {
                    foreach ($Field->getArray($fd) as $editorValue) {
                        $Field->addField($fd . '@html', RichEditor::render($editorValue));
                        $Field->addField($fd . '@title', RichEditor::renderTitle($editorValue));
                    }
                } elseif ($type === 'image' || $type === 'file') {
                    try {
                        $file = ACMS_Http::file($fd);
                        if ($type === 'file') {
                            if ($extensions = $this->Post->getArray($fd . '@extension')) {
                                $extension_entity = pathinfo($file->getName(), PATHINFO_EXTENSION);
                                $extension_matched = false;
                                foreach ($extensions as $extension) {
                                    if ($extension === $extension_entity) {
                                        $extension_matched = true;
                                    }
                                }
                                if (!$extension_matched) {
                                    throw new \RuntimeException('EXTENSION_IS_DIFFERENT');
                                }
                            }
                        }
                        $size = $file->getFileSize();
                        if (isset($Field->_aryMethod[$fd])) {
                            $arg = $Field->_aryMethod[$fd];
                            if (isset($arg['filesize'])) {
                                $maxsize = intval($arg['filesize']);
                                if ($size > ($maxsize * 1024)) {
                                    throw new \RuntimeException(UPLOAD_ERR_FORM_SIZE);
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        if ($e->getMessage() == 'EXTENSION_IS_DIFFERENT') {
                            $Field->setMethod($fd, 'extension', false);
                            continue;
                        }
                        if ($e->getMessage() == UPLOAD_ERR_INI_SIZE || $e->getMessage() == UPLOAD_ERR_FORM_SIZE) {
                            $Field->setMethod($fd, 'filesize', false);
                            $Field->set($fd, 'maxfilesize');
                            continue;
                        }
                    }
                }

                //-------
                // image
                if ('image' == $type) {
                    // data url
                    if (isset($_POST[$fd])) {
                        ACMS_POST_Image::base64DataToImage($_POST[$fd], $fd);
                        $Field->delete($fd);
                        $dataUrl = true;
                    }

                    if (empty($_FILES[$fd])) {
                        foreach (
                            [
                                'path', 'x', 'y', 'alt', 'fileSize',
                                'largePath', 'largeX', 'largeY', 'largeAlt', 'largeFileSize',
                                'tinyPath', 'tinyX', 'tinyY', 'tinyAlt', 'tinyFileSize',
                                'squarePath', 'squareX', 'squareY', 'squareAlt', 'squareFileSize',
                                'secret'
                            ] as $key
                        ) {
                            $key    = $fd . '@' . $key;
                            $this->deleteField->set($key, []);
                            $Field->deleteField($fd . '@' . $key);
                        }
                        continue;
                    }

                    $aryC   = [];
                    if (!is_array($_FILES[$fd]['tmp_name'])) {
                        $aryC[] = [
                            '_tmp_name' => $_FILES[$fd]['tmp_name'],
                            '_name'     => $_FILES[$fd]['name'],
                        ];
                    } else {
                        foreach ($_FILES[$fd]['tmp_name'] as $i => $tmp_name) {
                            $aryC[] = [
                                '_tmp_name' => $tmp_name,
                                '_name'     => $_FILES[$fd]['name'][$i],
                            ];
                        }
                    }

                    foreach (
                        [
                            'str'   => ['old', 'edit', 'alt', 'filename', 'extension', 'secret'],
                            'int'   => [
                                'width', 'height', 'size',
                                'tinyWidth', 'tinyHeight', 'tinySize',
                                'largeWidth', 'largeHeight', 'largeSize',
                                'squareWidth', 'squareHeight', 'squareSize',
                            ],
                        ] as $_type => $keys
                    ) {
                        foreach ($keys as $key) {
                            foreach ($aryC as $i => $c) {
                                $_field = $fd . '@' . $key;
                                $value  = $this->Post->isExists($_field, $i) ?
                                    $this->Post->get($_field, '', $i) : '';
                                $c[$key]    = ('int' == $_type) ? intval($value) : strval($value);
                                $aryC[$i]   = $c;
                            }
                            $this->Post->delete($fd . '@' . $key);
                        }
                    }

                    $aryData    = [];
                    foreach ($aryC as $c) {
                        $aryData[]  = [];
                    }
                    $cnt    = count($aryData);
                    for ($i = 0; $i < $cnt; $i++) {
                        $c          = $aryC[$i];
                        $data       =& $aryData[$i];

                        //-------------
                        // rawfilename
                        if (preg_match('/^@(.*)$/', $c['filename'], $match)) {
                            $c['filename']  = ('rawfilename' == $match[1]) ? date('Ym') . '/' . $c['_name'] : '';
                        }

                        //------------------------------------
                        // security check ( nullバイトチェック )
                        if ($c['old']      !== ltrim($c['old'])) {
                            continue;
                        }
                        if ($c['filename'] !== ltrim($c['filename'])) {
                            continue;
                        }

                        //-------------------------------------------------------------
                        // パスの半正規化 ( directory traversal対策・バイナリセーフ関数を使用 )
                        // この時点で //+ や ^/ は 混入する可能性はあるが無害とみなす
                        $c['old']      = preg_replace('/\.+\/+/', '', $c['old']);
                        $c['filename'] = preg_replace('/\.+\/+/', '', $c['filename']);

                        //---------------------------------------------
                        // 例外的無視ファイル
                        // pathの終端（ファイル名）が特定の場合にリジェクトする
                        if (!!preg_match('/\.htaccess$/', $c['filename'])) {
                            continue;
                        }

                        // アップロード処理中のファイルかどうか
                        $isProcessing = false;
                        foreach ($processingMediaFiles as $media) {
                            if ($media['path'] === $ARCHIVES_DIR . $c['old']) {
                                $isProcessing = true;
                                break;
                            }
                        }
                        //---------------------
                        // セキュリティチェック
                        // リクエストされた削除ファイル名が怪しい場合に削除と上書きをスキップ
                        // このチェックに引っかかった場合にもフィールドの情報は保持する(continueしない)
                        // 削除キーがDBに保存されていなかった場合などファイルが消せなくなるため
                        // 投稿者以上の権限を持っている場合にもチェックを行わない
                        // 暗号化は「フィールド名@パス」をmd5したもの
                        // 暗号化文字列の照合にDBは使えない
                        // 一回目にフォームを送信するときはDB上にデータがない
                        // アップロードが完了したにもかかわらず
                        // 他のエラーチェックで引っかかった時は
                        // DB上にデータは保存されないため比較できない
                        $secretCheck = ( 1
                            && !sessionWithSubscription()
                            && !empty($c['old'])
                            && ( 0
                                or 'delete' == $c['edit']
                                or !empty($c['_tmp_name'])
                            )
                        ) ? ($c['secret'] == md5($fd . '@' . $c['old'])) : true;

                        //----------------------------
                        // delete ( 指定削除 continue )
                        if (
                            1
                            && 'delete' == $c['edit']
                            && !empty($c['old'])
                            && $secretCheck
                            && !$isProcessing
                            && isExistsRuleModuleConfig()
                        ) {
                            if (!Entry::isNewVersion()) {
                                Image::deleteImageAllSize($ARCHIVES_DIR . normalSizeImagePath($c['old']));
                            }
                            continue;
                        }

                        //--------
                        // upload
                        if (!empty($c['_tmp_name']) and $secretCheck) {
                            $tmp_name   = $c['_tmp_name'];
                            if (!$dataUrl && !is_uploaded_file($tmp_name)) {
                                continue;
                            }
                            // getimagesizeが画像ファイルであるかの判定を兼用している
                            // @todo security:
                            // "GIF89a <SCRIPT>alert('xss');< /SCRIPT>のようなテキストファイルはgetimagesizeを通過する
                            // IE6, 7あたりはContent-Typeのほかにファイルの中身も評価してしまう
                            // 偽装テキストを読み込んだときに、HTML with JavaScriptとして実行されてしまう可能性がある
                            // 参考: http://www.tokumaru.org/d/20071210.html
                            if (!($xy = Storage::getImageSize($tmp_name))) {
                                continue;
                            }

                            //---------------------------
                            // delete ( 古いファイルの削除 )
                            if (
                                !empty($c['old']) &&
                                !$isProcessing &&
                                isExistsRuleModuleConfig()
                            ) {
                                if (!Entry::isNewVersion()) {
                                    Image::deleteImageAllSize($ARCHIVES_DIR . normalSizeImagePath($c['old']));
                                }
                            }

                            //------------------------------
                            // dirname, basename, extension
                            if (!empty($c['filename'])) {
                                if (!preg_match('@((?:[^/]*/)*)((?:[^.]*\.)*)(.*)$@', sprintf('%03d', BID) . '/' . $c['filename'], $match)) {
                                    trigger_error('unknown', E_USER_ERROR);
                                }

                                $extension  = !empty($match[3]) ? $match[3]
                                                                : Image::detectImageExtenstion($xy['mime']);
                                $dirname    = $match[1];
                                $basename   = !empty($match[2]) ? $match[2] . $extension
                                                                : uniqueString() . '.' . $extension;
                            } else {
                                $extension = !empty($c['extension'])
                                    ? $c['extension'] : Image::detectImageExtenstion($xy['mime']);
                                $dirname    = Storage::archivesDir();
                                $basename   = uniqueString() . '.' . $extension;
                            }

                            //-------
                            // angle
                            $angle  = 0;
                            if ('rotate' == substr($c['edit'], 0, 6)) {
                                $angle  = intval(substr($c['edit'], 6));
                            }

                            //--------
                            // normal
                            $normal     = $dirname . $basename;
                            $normalPath = $ARCHIVES_DIR . $normal;

                            // ファイル名が重複している場合はファイル名を変更する
                            $normalPath = Storage::uniqueFilePath($normalPath);
                            $normal = mb_substr($normalPath, strlen($ARCHIVES_DIR));
                            $basename = Storage::mbBasename($normalPath);

                            Image::copyImage($tmp_name, $normalPath, $c['width'], $c['height'], $c['size'], $angle);

                            if ($xy = Storage::getImageSize($normalPath)) {
                                $data[$fd . '@path']  = $normal;
                                $data[$fd . '@x']     = $xy[0];
                                $data[$fd . '@y']     = $xy[1];
                                $data[$fd . '@alt']   = $c['alt'];
                                $data[$fd . '@fileSize'] = filesize($normalPath);

                                $processingMediaFiles[] = [
                                    'path'  => $normalPath,
                                ];
                                Entry::addUploadedFiles($normal); // 新規バージョンとして作成する時にファイルをCOPYするかの判定に利用
                            }

                            //-------
                            // large
                            if (!empty($c['largeWidth']) or !empty($c['largeHeight']) or !empty($c['largeSize'])) {
                                $large     = $dirname . 'large-' . $basename;
                                $largePath = $ARCHIVES_DIR . $large;
                                if (!Storage::exists($largePath)) {
                                    Image::copyImage($tmp_name, $largePath, $c['largeWidth'], $c['largeHeight'], $c['largeSize'], $angle);
                                }
                                if ($xy = Storage::getImageSize($largePath)) {
                                    $data[$fd . '@largePath'] = $large;
                                    $data[$fd . '@largeX']    = $xy[0];
                                    $data[$fd . '@largeY']    = $xy[1];
                                    $data[$fd . '@largeAlt']  = $c['alt'];
                                    $data[$fd . '@largeFileSize']  = filesize($largePath);

                                    $processingMediaFiles[] = [
                                        'path'  => $normalPath,
                                    ];
                                }
                            }

                            //------
                            // tiny
                            if (!empty($c['tinyWidth']) or !empty($c['tinyHeight']) or !empty($c['tinySize'])) {
                                $tiny     = $dirname . 'tiny-' . $basename;
                                $tinyPath = $ARCHIVES_DIR . $tiny;
                                if (!Storage::exists($tinyPath)) {
                                    Image::copyImage($tmp_name, $tinyPath, $c['tinyWidth'], $c['tinyHeight'], $c['tinySize'], $angle);
                                }
                                if ($xy = Storage::getImageSize($tinyPath)) {
                                    $data[$fd . '@tinyPath']  = $tiny;
                                    $data[$fd . '@tinyX']     = $xy[0];
                                    $data[$fd . '@tinyY']     = $xy[1];
                                    $data[$fd . '@tinyAlt']   = $c['alt'];
                                    $data[$fd . '@tinyFileSize']  = filesize($tinyPath);

                                    $processingMediaFiles[] = [
                                        'path'  => $normalPath,
                                    ];
                                }
                            }

                            //---------
                            // square
                            if (!empty($c['squareWidth']) or !empty($c['squareHeight']) or !empty($c['squareSize'])) {
                                $square   = $dirname . 'square-' . $basename;
                                $squarePath = $ARCHIVES_DIR . $square;
                                $squareSize = 0;
                                if (!empty($c['squareWidth'])) {
                                    $squareSize = $c['squareWidth'];
                                } elseif (!empty($c['squareHeight'])) {
                                    $squareSize = $c['squareHeight'];
                                } elseif (!empty($c['squareSize'])) {
                                    $squareSize = $c['squareSize'];
                                }

                                if (!Storage::exists($squarePath)) {
                                    Image::copyImage($tmp_name, $squarePath, $squareSize, $squareSize, $squareSize, $angle);
                                }
                                if ($xy = Storage::getImageSize($squarePath)) {
                                    $data[$fd . '@squarePath']  = $square;
                                    $data[$fd . '@squareX']     = $xy[0];
                                    $data[$fd . '@squareY']     = $xy[1];
                                    $data[$fd . '@squareAlt']   = $c['alt'];
                                    $data[$fd . '@squareFileSize']  = filesize($squarePath);

                                    $processingMediaFiles[] = [
                                        'path'  => $normalPath,
                                    ];
                                }
                            }

                            //--------
                            // secret
                            // 正しくファイルがアップロードされた場合のみ新しくキーを発行する
                            $data[$fd . '@secret'] = md5($fd . '@' . $normal);

                            continue;
                        }

                        //-----
                        // old
                        // 非編集アップデートの時
                        if (!empty($c['old'])) {
                            //--------
                            // normal
                            $normal = $c['old'];
                            $normalPath = $ARCHIVES_DIR . $normal;
                            if ($xy = Storage::getImageSize($normalPath)) {
                                $data[$fd . '@path']  = $normal;
                                $data[$fd . '@x']     = $xy[0];
                                $data[$fd . '@y']     = $xy[1];
                                $data[$fd . '@alt']   = $c['alt'];
                                $data[$fd . '@fileSize'] = filesize($normalPath);

                                if (!preg_match('@((?:[^/]*/)*)((?:[^.]*\.)*)(.*)$@', $normal, $match)) {
                                    trigger_error('unknown', E_USER_ERROR);
                                }
                                $extension  = $match[3];
                                $dirname    = $match[1];
                                $basename   = $match[2] . $extension;

                                //-------
                                // large
                                $large     = $dirname . 'large-' . $basename;
                                $largePath = $ARCHIVES_DIR . $large;
                                if ($xy = Storage::getImageSize($largePath)) {
                                    $data[$fd . '@largePath'] = $large;
                                    $data[$fd . '@largeX']    = $xy[0];
                                    $data[$fd . '@largeY']    = $xy[1];
                                    $data[$fd . '@largeAlt']  = $c['alt'];
                                    $data[$fd . '@largeFileSize']  = filesize($largePath);
                                }

                                //------
                                // tiny
                                $tiny     = $dirname . 'tiny-' . $basename;
                                $tinyPath = $ARCHIVES_DIR . $tiny;
                                if ($xy = Storage::getImageSize($tinyPath)) {
                                    $data[$fd . '@tinyPath']  = $tiny;
                                    $data[$fd . '@tinyX']     = $xy[0];
                                    $data[$fd . '@tinyY']     = $xy[1];
                                    $data[$fd . '@tinyAlt']   = $c['alt'];
                                    $data[$fd . '@tinyFileSize']  = filesize($tinyPath);
                                }

                                //------
                                // square
                                $square   = $dirname . 'square-' . $basename;
                                $squarePath = $ARCHIVES_DIR . $square;
                                if ($xy = Storage::getImageSize($squarePath)) {
                                    $data[$fd . '@squarePath']  = $square;
                                    $data[$fd . '@squareX']     = $xy[0];
                                    $data[$fd . '@squareY']     = $xy[1];
                                    $data[$fd . '@squareAlt']   = $c['alt'];
                                    $data[$fd . '@squareFileSize']  = filesize($squarePath);
                                }


                                //--------
                                // secret
                                // これはエラー時にフォームを再表示しなければならない場合に必要
                                $data[$fd . '@secret']  = $c['secret'];
                            }
                        }
                    }

                    //------------
                    // save field
                    $cnt        = count($aryData);
                    foreach (
                        [
                            'path', 'x', 'y', 'alt', 'fileSize',
                            'largePath', 'largeX', 'largeY', 'largeAlt', 'largeFileSize',
                            'tinyPath', 'tinyX', 'tinyY', 'tinyAlt', 'tinyFileSize',
                            'squarePath', 'squareX', 'squareY', 'squareAlt', 'squareFileSize',
                            'secret'
                        ] as $key
                    ) {
                        $key    = $fd . '@' . $key;
                        $value  = [];
                        for ($i = 0; $cnt > $i; $i++) {
                            $value[] = !empty($aryData[$i][$key]) ? $aryData[$i][$key] : ''; // @phpstan-ignore-line
                        }
                        $Field->set($key, $value);

                        //------------
                        // validation
                        foreach ($this->Post->listFields() as $_fd) {
                            if (preg_match('/^' . $key . ':(?:v#|validator#)(.+)$/', $_fd, $match)) {
                                $method = $match[1];
                                $Field->setMethod($key, $method, $this->Post->get($_fd));
                                $this->Post->delete($_fd);
                            }
                        }
                    }

                //------
                // file
                } elseif ('file' == $type) {
                    if (empty($_FILES[$fd])) {
                        $this->deleteField->setField($fd . '@path', []);
                        $this->deleteField->setField($fd . '@baseName', []);
                        $this->deleteField->setField($fd . '@fileSize', []);
                        $this->deleteField->setField($fd . '@secret', []);
                        $this->deleteField->setField($fd . '@downloadName', []);

                        $Field->deleteField($fd . '@path');
                        $Field->deleteField($fd . '@baseName');
                        $Field->deleteField($fd . '@fileSize');
                        $Field->deleteField($fd . '@secret');
                        $Field->deleteField($fd . '@downloadName');

                        continue;
                    }

                    $aryC   = [];
                    if (!is_array($_FILES[$fd]['tmp_name'])) {
                        $aryC[] = [
                            '_tmp_name' => $_FILES[$fd]['tmp_name'],
                            '_name'     => $_FILES[$fd]['name'],
                        ];
                    } else {
                        foreach ($_FILES[$fd]['tmp_name'] as $i => $tmp_name) {
                            $aryC[] = [
                                '_tmp_name' => $tmp_name,
                                '_name'     => $_FILES[$fd]['name'][$i],
                            ];
                        }
                    }

                    //--------------------------
                    // field copy to local vars
                    foreach (['old', 'edit', 'extension', 'filename', 'secret', 'fileSize', 'downloadName', 'originalName', 'baseName'] as $key) {
                        foreach ($aryC as $i => $c) {
                            $_field = $fd . '@' . $key;
                            if ($key === 'extension') {
                                $c[$key] = $this->Post->isExists($_field, $i) ?
                                    $this->Post->getArray($_field) : '';
                            } else {
                                $c[$key] = $this->Post->isExists($_field, $i) ?
                                    $this->Post->get($_field, '', $i) : '';
                            }
                            $aryC[$i] = $c;
                        }
                        $this->Post->delete($fd . '@' . $key);
                    }

                    // 参照用の配列を作成して，ファイル数の分だけインデックスを初期化
                    $aryPath    = [];
                    $aryName    = [];
                    $aryOriginalName = [];
                    $aryDownloadName = [];
                    $arySize    = [];
                    $arySecret  = [];
                    foreach ($aryC as $c) {
                        $aryPath[] = $aryName[] = $aryOriginalName[] = $aryDownloadName[] = $arySize[] = $arySecret[] = '';
                    }

                    $cnt    = count($aryPath);

                    for ($i = 0; $i < $cnt; $i++) {
                        $c      = $aryC[$i];
                        // 各配列のインデックス位置を，ローカル変数に参照させる
                        $_path  =& $aryPath[$i];
                        $_name  =& $aryName[$i];
                        $_orginal_name =& $aryOriginalName[$i];
                        $_download_name =& $aryDownloadName[$i];
                        $_size  =& $arySize[$i];
                        $_secret=& $arySecret[$i];

                        //-------------
                        // rawfilename
                        if (preg_match('/^@(.*)$/', $c['filename'], $match)) {
                            $c['filename']  = ('rawfilename' == $match[1]) ? date('Ym') . '/' . $c['_name'] : '';
                        }

                        //------------------------------------
                        // security check ( nullバイトチェック )
                        if ($c['old']      !== ltrim($c['old'])) {
                            continue;
                        }
                        if ($c['filename'] !== ltrim($c['filename'])) {
                            continue;
                        }

                        //-------------------------------------------------------------
                        // パスの半正規化 ( directory traversal対策・バイナリセーフ関数を使用 )
                        // この時点で //+ や ^/ は 混入する可能性はあるが無害とみなす
                        $c['old']      = preg_replace('/\.+\/+/', '', $c['old']);
                        $c['filename'] = preg_replace('/\.+\/+/', '', $c['filename']);

                        //---------------------------------------------
                        // 例外的無視ファイル
                        // pathの終端（ファイル名）が特定の場合にリジェクトする
                        if (!!preg_match('/\.htaccess$/', $c['filename'])) {
                            continue;
                        }

                        // アップロード処理中のファイルかどうか
                        $isProcessing = false;
                        foreach ($processingMediaFiles as $media) {
                            if ($media['path'] === $ARCHIVES_DIR . $c['old']) {
                                $isProcessing = true;
                                break;
                            }
                        }

                        //---------------------
                        // シークレットチェック
                        $secretCheck = ( 1
                            && !sessionWithContribution()
                            && !empty($c['old'])
                            && ( 0
                                or 'delete' == $c['edit']
                                or !empty($c['_tmp_name'])
                            )
                        ) ? ($c['secret'] == md5($fd . '@' . $c['old'])) : true;

                        //----------------------------
                        // delete ( 指定削除 continue )
                        if ('delete' === $c['edit'] && !empty($c['old']) && $secretCheck && !$isProcessing) {
                            if (!Entry::isNewVersion()) {
                                Storage::remove($ARCHIVES_DIR . $c['old']);
                                if (HOOK_ENABLE) {
                                    $Hook = ACMS_Hook::singleton();
                                    $Hook->call('mediaDelete', $ARCHIVES_DIR . $c['old']);
                                }
                            }
                            continue;
                        }

                        //--------
                        // upload
                        if (!empty($c['_tmp_name']) and $secretCheck) {
                            $tmp_name   = $c['_tmp_name'];
                            if (!is_uploaded_file($tmp_name)) {
                                continue;
                            }
                            // 拡張子がなければリジェクト
                            if (!preg_match('@\.([^.]+)$@', $c['_name'], $match)) {
                                continue;
                            }

                            // テキストファイル（=PHPなどのスクリプトファイル）判定
                            // ファイルの先頭1000行を取得
                            // 文字コードが判別不能な文字列をバイナリとみなす
                            if ('on' == config('file_prohibit_textfile')) {
                                $fp = fopen($c['_tmp_name'], 'rb');
                                $readedLine = 0;
                                $sampleLine = 1000;
                                $sample = '';

                                while (($line = fgets($fp, 4096)) !== false) {
                                    if ($readedLine++ > $sampleLine) {
                                        break;
                                    }
                                    $sample .= $line;
                                }

                                fclose($fp);

                                // @todo security:
                                // mb_detect_encodingを利用しているが、これはUTF-16を判定できないため、バイナリファイルと見なしてしまう
                                // 冒頭をUTF-16、以後をUTF-8にすることで不正なテキストファイルをarchivesにアップロードできる可能性がある
                                // ただし、htaccessをいじられたりしない限りは基本的に問題にならない（通常はPHP等として実行できない）
                                if (false !== detectEncode($sample)) {
                                    continue;
                                }
                            }

                            //------------------------------
                            // dirname, basename, extension
                            // アップロードされた実ファイルの拡張子が実質的に利用される
                            // extensionオプションや、filenameオプションの制限は、
                            // 意図する拡張子のファイルがアップロードされているかのチェックのみに使われる

                            // 実ファイルの拡張子
                            $extension  = $match[1];

                            if (!empty($c['filename'])) {
                                if (!preg_match('@((?:[^/]*/)*)((?:[^.]*\.)*)(.*)$@', sprintf('%03d', BID) . '/' . $c['filename'], $match)) {
                                    trigger_error('unknown', E_USER_ERROR);
                                }

                                // @filenameオプションの拡張子
                                $c['filename_extension']  = $match[3];

                                // @filenameオプションの指定内に拡張子がないと，ファイル名とファイル名の拡張子が同一になる | @todo issue: 先行する正規表現を改善する
                                // ディレクトリのみでファイル名は無指定の場合は、拡張子が空になる
                                //   =>  ファイル名拡張子でチェックする意図がないものとして、filename_extensionをunsetし、以降の拡張子チェックから除外する
                                if ($c['filename'] === $c['filename_extension'] || empty($c['filename_extension'])) {
                                    unset($c['filename_extension']);
                                }

                                $dirname    = $match[1];
                                $basename   = !empty($match[2]) ? $match[2] . $extension      // basenameは実ファイルの拡張子とする
                                                                : uniqueString() . '.' . $extension;
                            } else {
                                $dirname    = Storage::archivesDir();
                                $basename   = uniqueString() . '.' . $extension;
                            }

                            if (
                                1
                                // "実ファイルの拡張子" が "アップロード許可拡張子コンフィグ" に含まれていること
                                and in_array($extension, $allow_file_extensions, true)

                                // 拡張子指定オプションが空でなければ...
                                and ( empty($c['extension']) or
                                    (
                                        // "拡張子指定オプション" が "アップロード許可拡張子コンフィグ" に含まれていること
                                        array_in_array($c['extension'], $allow_file_extensions)

                                        // さらに，"拡張子指定オプション" と "実ファイルの拡張子" が一致すること
                                        and in_array($extension, $c['extension'], true))
                                    )

                                // ファイル名オプションの拡張子が未定義でなければ...
                                and ( !isset($c['filename_extension']) or
                                    (
                                        // "ファイル名オプションの拡張子" が "アップロード許可拡張子コンフィグ" に含まれていること
                                        in_array($c['filename_extension'], $allow_file_extensions, true))

                                        // さらに，"ファイル名オプションの拡張子" と "実ファイルの拡張子" が一致すること
                                        and $c['filename_extension'] === $extension
                                    )

                                // 保存先ディレクトリの再帰的作成
                                and Storage::makeDirectory($ARCHIVES_DIR . $dirname)
                            ) {
                                //---------------------------
                                // delete ( 古いファイルの削除 )
                                if (!empty($c['old']) && !$isProcessing && !Entry::isNewVersion()) {
                                    Storage::remove($ARCHIVES_DIR . $c['old']);
                                    if (HOOK_ENABLE) {
                                        $Hook = ACMS_Hook::singleton();
                                        $Hook->call('mediaDelete', $ARCHIVES_DIR . $c['old']);
                                    }
                                }

                                //------
                                // copy
                                $path     = $dirname . $basename;
                                $realpath = $ARCHIVES_DIR . $path;
                                Entry::addUploadedFiles($path); // 新規バージョンとして作成する時にファイルをCOPYするかの判定に利用

                                // 重複対応
                                $realpath = Storage::uniqueFilePath($realpath);
                                $path = mb_substr($realpath, strlen($ARCHIVES_DIR));

                                Storage::copy($tmp_name, $realpath);

                                $processingMediaFiles[] = [
                                    'path'  => $realpath,
                                ];

                                if (HOOK_ENABLE) {
                                    $Hook = ACMS_Hook::singleton();
                                    $Hook->call('mediaCreate', $realpath);
                                }

                                //-----
                                // set
                                $_path  = $path;
                                $_name  = Storage::mbBasename($realpath);
                                $_orginal_name = $c['_name'];
                                $_download_name = $c['downloadName'];
                                $_size  = filesize($realpath);
                                $_secret = md5($fd . '@' . $path);
                                continue;
                            } else {
                                $Field->setMethod($fd, 'inValidFile', false);
                            }
                        }

                        //-----
                        // old
                        // 非編集アップデートの時
                        if (!empty($c['old'])) {
                            $_path  = $c['old'];
                            $_name = $c['baseName'];
                            $_orginal_name = $c['originalName'];
                            $_download_name = $c['downloadName'];
                            $_size  = $c['fileSize'];
                            $_secret = $c['secret'];
                            continue;
                        }
                    }

                    //-----------
                    // set field
                    $Field->setField($fd . '@path', $aryPath);
                    $Field->setField($fd . '@baseName', $aryName);
                    $Field->setField($fd . '@fileSize', $arySize);
                    $Field->setField($fd . '@secret', $arySecret);
                    $Field->setField($fd . '@originalName', $aryOriginalName);
                    $Field->setField($fd . '@downloadName', $aryDownloadName);

                    //------------
                    // validation
                    $key    = $fd . '@path';
                    foreach ($this->Post->listFields() as $_fd) {
                        if (preg_match('/^' . $key . ':(?:v#|validator#)(.+)$/', $_fd, $match)) {
                            $method = $match[1];
                            $Field->setMethod($key, $method, $this->Post->get($_fd));
                            $this->Post->delete($_fd);
                        }
                    }
                }


                continue;
            }
        }

        //--------
        // search
        foreach ($Field->listFields() as $fd) {
            // topic-fix_field_search: Field::getがnullを返さなくなっていたので，無指定時の戻りを擬似定数に変更して対処
            $s  = $this->Post->get($fd . ':search', '__NOT_SPECIFIED__');
            if ($s === '__NOT_SPECIFIED__') {
                if (is_int(strpos($fd, '@'))) {
                    $s  = '0';
                } else {
                    $s  = '1';
                }
            }
            $Field->setMeta($fd, 'search', !empty($s));
            $this->Post->deleteField($fd . ':search');
        }

        $Field->validate($V);

        return $Field;
    }

    /**
     * @return array
     */
    public function getJsModules()
    {
        $Session    =& Field::singleton('session');
        $delStorage = $Session->get('webStorageDeleteKey');

        jsModule('offset', DIR_OFFSET);
        jsModule('jsDir', JS_DIR);
        jsModule('themesDir', '/' . DIR_OFFSET . THEMES_DIR);
        jsModule('bid', BID);
        jsModule('aid', AID);
        jsModule('uid', UID);
        jsModule('cid', CID);
        jsModule('eid', EID);
        jsModule('rvid', RVID);
        jsModule('bcd', htmlspecialchars(ACMS_RAM::blogCode(BID), ENT_QUOTES));
        jsModule('rid', $this->Get->get('rid', null));
        jsModule('mid', $this->Get->get('mid', null));
        jsModule('setid', $this->Get->get('setid', null));
        jsModule('layout', LAYOUT_EDIT);
        jsModule('googleApiKey', config('google_api_key'));
        jsModule('jQuery', config('jquery_version'));
        jsModule('jQueryMigrate', config('jquery_migrate', 'off'));
        jsModule('mediaClientResize', config('media_client_resize', 'on'));
        jsModule('delStorage', $delStorage);
        jsModule('fulltimeSSL', (SSL_ENABLE and FULLTIME_SSL_ENABLE) ? 1 : 0);
        jsModule('v', md5(VERSION));
        jsModule('dbCharset', DB_CONNECTION_CHARSET);
        jsModule('auth', ACMS_RAM::userAuth(SUID));

        jsModule('umfs', ini_get('upload_max_filesize'));
        jsModule('pms', ini_get('post_max_size'));
        jsModule('mfu', ini_get('max_file_uploads'));
        jsModule('lgImg', config('image_size_large_criterion') . ':' . preg_replace('/[^0-9]/', '', config('image_size_large')));
        jsModule('jpegQuality', config('image_jpeg_quality', 75));
        jsModule('mediaLibrary', config('media_library'));
        jsModule('edition', LICENSE_EDITION);
        jsModule('urlPreviewExpire', config('url_preview_expire'));
        jsModule('timemachinePreviewDefaultDevice', config('timemachine_preview_default_device'));
        jsModule('timemachinePreviewHasHistoryDevice', config('timemachine_preview_has_history_device'));

        if ($Session->get('timemachine_datetime')) {
            jsModule('timeMachineMode', 'true');
        }
        if (sessionWithAdministration()) {
            jsModule('rootTpl', ROOT_TPL);
        }
        if (defined('IS_EDITING_ENTRY') && IS_EDITING_ENTRY) {
            $Session->delete('webStorageDeleteKey');
        }

        //--------------
        // multi domain
        jsModule('multiDomain', '0');
        if (defined('LICENSE_OPTION_PLUSDOMAIN') && intval(LICENSE_OPTION_PLUSDOMAIN) > 0) {
            $SQL = SQL::newSelect('blog');
            $SQL->setSelect('DISTINCT blog_domain', 'domains', null, 'COUNT');
            $domain_num = DB::query($SQL->get(dsn()), 'one');
            if (intval($domain_num) > 1) {
                jsModule('multiDomain', '1');
            }
        }

        //----------
        // category
        if ($cid = CID) {
            $ccds   = [ACMS_RAM::categoryCode($cid)];
            while ($cid = ACMS_RAM::categoryParent($cid)) {
                if ('on' == ACMS_RAM::categoryIndexing($cid)) {
                    $ccds[] = htmlspecialchars(ACMS_RAM::categoryCode($cid), ENT_QUOTES);
                }
            }
            jsModule('ccd', join('/', array_reverse($ccds)));
        }

        //---------
        // session
        jsModule('admin', ADMIN);
        jsModule('rid', RID);
        jsModule('ecd', ACMS_RAM::entryCode(EID));
        jsModule('keyword', htmlspecialchars(str_replace('　', ' ', KEYWORD), ENT_QUOTES));
        jsModule('scriptRoot', '/' . DIR_OFFSET . (REWRITE_ENABLE ? '' : SCRIPT_FILENAME . '/'));

        //-------
        // cache
        if (config('javascript_nocache') === 'on') {
            jsModule('cache', uniqueString());
        }

        $jsModules  = [];
        foreach (jsModule() as $key => $value) {
            if (empty($value)) {
                continue;
            }
            if ($key === 'domains') {
                $value = implode(',', $value);
            }
            $value = htmlspecialchars($value, ENT_QUOTES);
            $jsModules[] = $key . (!is_bool($value) ? '=' . $value : '');
        }

        return $jsModules;
    }

    /**
     * a-blog cms で管理しているドメインのURLかチェックする
     *
     * @param string $url
     * @return bool
     */
    public function isSafeUrl($url)
    {
        if (0 !== strpos($url, 'http')) {
            return true;
        }
        $sql = SQL::newSelect('blog');
        $sql->setSelect('DISTINCT blog_domain');
        $domains = DB::query($sql->get(dsn()), 'list');

        $sql = SQL::newSelect('alias');
        $sql->setSelect('DISTINCT alias_domain');
        $domains = array_merge($domains, DB::query($sql->get(dsn()), 'list'));

        $host = parse_url($url, PHP_URL_HOST);

        if (in_array($host, $domains, true)) {
            return true;
        }
        return false;
    }

    /**
     * @param $data
     */
    public function responseJson($data)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo(json_encode($data));
        die();
    }

    /**
     * @param string $lockKey
     */
    public function logLockPost($lockKey)
    {
        if (empty($lockKey)) {
            return;
        }
        $sql = SQL::newInsert('lock_source');
        $sql->addInsert('lock_source_key', $lockKey);
        $sql->addInsert('lock_source_address', REMOTE_ADDR);
        $sql->addInsert('lock_source_datetime', date('Y-m-d H:i:s', REQUEST_TIME));

        DB::query($sql->get(dsn()), 'exec');
    }

    /**
     * @param string $lockKey
     * @param int $trialTime 試行時間
     * @param int $trialNumber 試行回数
     * @param int $lockTime ロックタイム
     * @param bool $remoteAddr 接続元IPアドレスをチェックするかどうか
     * @return bool
     */
    public function validateLockPost($lockKey, $trialTime = 5, $trialNumber = 5, $lockTime = 15, $remoteAddr = true)
    {
        // 秒に変換
        $trialTime = $trialTime * 60;
        $lockTime = $lockTime * 60;

        // ロックされているか判定
        $sql = SQL::newSelect('lock');
        $sql->addWhereOpr('lock_key', $lockKey);
        if ($remoteAddr) {
            $sql->addWhereOpr('lock_address', REMOTE_ADDR);
        }
        $sql->addWhereOpr('lock_datetime', date('Y-m-d H:i:s', REQUEST_TIME - $lockTime), '>');
        if (DB::query($sql->get(dsn()), 'one')) {
            return false;
        }

        $sql = SQL::newSelect('lock_source');
        $sql->addSelect('*', 'trialCount', null, 'COUNT');
        $sql->addWhereOpr('lock_source_key', $lockKey);
        if ($remoteAddr) {
            $sql->addWhereOpr('lock_source_address', REMOTE_ADDR);
        }
        $sql->addWhereOpr('lock_source_datetime', date('Y-m-d H:i:s', REQUEST_TIME - $trialTime), '>');
        $trialCount = DB::query($sql->get(dsn()), 'one');
        if ($trialCount >= $trialNumber) {
            // 試行回数を超えたのでロック
            AcmsLogger::notice('試行回数を超えたのでロックしました', [
                'lockKey' => $lockKey,
                'trialTime' => $trialTime,
                'trialNumber' => $trialNumber,
                'lockTime' => $lockTime,
            ]);

            $sql = SQL::newInsert('lock');
            $sql->addInsert('lock_key', $lockKey);
            $sql->addInsert('lock_datetime', date('Y-m-d H:i:s', REQUEST_TIME));
            $sql->addInsert('lock_address', REMOTE_ADDR);
            DB::query($sql->get(dsn()), 'exec');

            $sql = SQL::newDelete('lock_source');
            $sql->addWhereOpr('lock_source_key', $lockKey);
            if ($remoteAddr) {
                $sql->addWhereOpr('lock_source_address', REMOTE_ADDR);
            }
            DB::query($sql->get(dsn()), 'exec'); // @phpstan-ignore-line
            return false;
        }
        $sql = SQL::newDelete('lock');
        $sql->addWhereOpr('lock_key', $lockKey);
        if ($remoteAddr) {
            $sql->addWhereOpr('lock_address', REMOTE_ADDR);
        }
        DB::query($sql->get(dsn()), 'exec'); // @phpstan-ignore-line

        // １ヶ月前のログは削除
        $sql = SQL::newDelete('lock_source');
        $sql->addWhereOpr('lock_source_datetime', date('Y-m-d H:i:s', REQUEST_TIME - 2764800), '<');
        DB::query($sql->get(dsn()), 'exec'); // @phpstan-ignore-line

        return true;
    }

    /**
     * @param $str
     * @return string
     */
    public function camelize($str)
    {
        return lcfirst(strtr(ucwords(strtr($str, ['_' => ' '])), [' ' => '']));
    }

    /**
     * @param false $noCache
     */
    public function clientCacheHeader($noCache = false)
    {
        $cacheExpireClient = intval(config('cache_expire_client'));
        if (
            1
            && !ACMS_POST
            && ('200' == substr(httpStatusCode(), 0, 3))
            && !ACMS_SID
            && $cacheExpireClient > 0
            && !$noCache
        ) {
            header('Cache-Control: public, max-age=' . $cacheExpireClient);
            header('Last-Modified: ' . getRFC2068Time(REQUEST_TIME));
            header('Expires: ' . getRFC2068Time(REQUEST_TIME + $cacheExpireClient));
        } elseif (
            0
            || ACMS_POST
            || ('200' !== substr(httpStatusCode(), 0, 3))
            || ACMS_SID
            || $noCache
        ) {
            header('Cache-Control: no-store, max-age=0'); // HTTP/1.1
            header('Pragma: no-cache'); // HTTP/1.0
            header('Expires: 0');
        }
    }

    /**
     * @param string $chid
     * @param string $contents
     * @param string $mime
     */
    public function saveCache($chid, $contents, $mime)
    {
        $no_cache_page = false;
        /** @var \Acms\Services\Cache\Adapters\Tag $pageCache */
        $pageCache = Cache::page();

        if (
            0
            || (defined('NO_CACHE_PAGE') && NO_CACHE_PAGE)
            || strtoupper($_SERVER['REQUEST_METHOD']) !== 'GET'
        ) {
            $no_cache_page = true;
        }
        if (
            !!$chid &&
            !$no_cache_page &&
            '200 OK' === httpStatusCode()
        ) {
            $tagBid = 'bid-' . BID;
            $tagEid = 'eid-' . EID;
            $value = [
                'mime' => $mime,
                'charset' => config('charset'),
                'createdAt' => REQUEST_TIME,
                'data' => $contents,
            ];
            $lifetime = intval(config('cache_expire'));
            $pageCache->put($chid, $value, $lifetime, [$tagBid, $tagEid]);
        }
    }

    /**
     * 例外情報を連想配列に変換
     *
     * @param \Throwable $th
     * @param array $add
     * @return (string|int)[]
     */
    public function exceptionArray(\Throwable $th, array $add = []): array
    {
        $array = [
            'message' => $th->getMessage(),
            'file' => $th->getFile(),
            'line' => $th->getLine(),
            'trace' => getExceptionTraceAsString($th),
        ];
        return array_merge($array, $add);
    }

    /**
     * ファイルアップロードを検証
     * @param string $name
     * @return void
     * @throws RuntimeException
     */
    public function validateFileUpload($name)
    {
        if (isset($_FILES[$name]['error'])) {
            switch ($_FILES[$name]['error']) {
                case UPLOAD_ERR_OK:
                    break;
                case UPLOAD_ERR_INI_SIZE:
                    throw new \RuntimeException('アップロードされたファイルが大きすぎます');
                case UPLOAD_ERR_FORM_SIZE:
                    throw new \RuntimeException('アップロードされたファイルが大きすぎます');
                case UPLOAD_ERR_PARTIAL:
                    throw new \RuntimeException('通信エラーにより、正常にアップロードできませんでした');
                case UPLOAD_ERR_NO_FILE:
                    throw new \RuntimeException('ファイルがアップロードされませんでした');
                case UPLOAD_ERR_NO_TMP_DIR:
                    throw new \RuntimeException('一時ディレクトリがないためアップロードできませんでした');
                case UPLOAD_ERR_CANT_WRITE:
                    throw new \RuntimeException('ファイルの書き込みに失敗しました');
                case UPLOAD_ERR_EXTENSION:
                    throw new \RuntimeException('アップロードが拡張モジュールによって停止されました');
                default:
                    throw new \RuntimeException('不明なエラー');
            }
        }
        if (!is_uploaded_file($_FILES[$name]['tmp_name'])) {
            throw new \RuntimeException('アップロードされたファイルがありません');
        }
    }

    /**
     * MIMEタイプをパスから取得
     *
     * @param string $path
     * @return string|false
     */
    public function getMimeType(string $path)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $path);
        finfo_close($finfo);

        return $mimeType;
    }
}
