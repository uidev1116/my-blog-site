<?php

namespace Acms\Services\Unit\Contracts;

interface PrimaryImageUnit
{
    /**
     * メイン画像のパスを取得。メディアの場合メディアIDを取得
     *
     * @return array
     */
    public function getPaths(): array;

    /**
     * メイン画像のAltを取得
     *
     * @return array
     */
    public function getAlts(): array;

    /**
     * メイン画像のキャプションを取得
     *
     * @return array
     */
    public function getCaptions(): array;
}
