<?php

namespace Acms\Services\Logger;

class Level
{
    /**
     * デバッグ
     * DEBUG => 100
     * 原因が特定できない不具合のデバッグのために一時的に仕込む場合に使用。
     */
    public const DEBUG = 100;

    /**
     * 情報
     * INFO => 200
     * エラーではない正常の操作を記憶。監査ログなどで使用。
     */

    public const INFO = 200;

    /**
     * 注意
     * NOTICE => 250
     * 特にプログラムを修正する必要はないが、不正操作・不正アクセス（CSRFチェック、アカウントロック時）などで使用。
     */

    public const NOTICE = 250;

    /**
     * 警告
     * WARNING => 300
     * 潜在的な問題。不具合や環境に問題がある可能性があるエラー。問題が発生していても，処理が継続できる場合に使用。
     */
    public const WARNING = 300;

    /**
     * エラー
     * ERROR => 400
     * データが壊れているなど、不具合や環境に問題がある可能性があるエラー。処理が継続できない場合に使用。
     */
    public const ERROR = 400;

    /**
     * 重大
     * CRITICAL => 500
     * 一部機能が使用不能・表示不能になったなどの、ある程度影響範囲が大きいエラーが起きた場合に使用。
     */
    public const CRITICAL = 500;

    /**
     * 警報
     * ALERT => 550
     * データベースに接続できないなど、サイトが表示できない状態で緊急で対応が必要な場合に使用。
     */

    public const ALERT = 550;

    /**
     * 緊急
     * EMERGENCY => 600
     * サイトが表示できない状態。基本的には使用しない。
     */
    public const EMERGENCY = 600;

    /**
     * 日本語変換テーブル
     * @var string[]
     */
    protected static $levels = [
        self::DEBUG => 'デバッグ',
        self::INFO => '情報',
        self::NOTICE => '注意',
        self::WARNING => '警告',
        self::ERROR => 'エラー',
        self::CRITICAL => '重大',
        self::ALERT => '緊急',
        self::EMERGENCY => '緊急',
    ];

    /**
     * ラベル-値変換テーブル
     * @var int[]
     */
    protected static $values = [
        'DEBUG' => self::DEBUG,
        'INFO' => self::INFO,
        'NOTICE' => self::NOTICE,
        'WARNING' => self::WARNING,
        'ERROR' => self::ERROR,
        'CRITICAL' => self::CRITICAL,
        'ALERT' => self::ALERT,
        'EMERGENCY' => self::EMERGENCY,
    ];

    /**
     * 日本語のログレベルのラベルを変換
     * @param int $level
     * @return string
     */
    public static function getLevelNameJa(int $level): string
    {
        if (!isset(static::$levels[$level])) {
            return '不明なエラー';
        }
        return static::$levels[$level];
    }

    /**
     * 英語のログレベルからintの値を取得
     * @param string $level
     * @return int
     */
    public static function getLevelValue(string $level): int
    {
        if (!isset(static::$values[$level])) {
            return 0;
        }
        return static::$values[$level];
    }
}
