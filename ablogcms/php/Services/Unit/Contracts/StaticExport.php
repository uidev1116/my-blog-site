<?php

namespace Acms\Services\Unit\Contracts;

interface StaticExport
{
    /**
     * 静的書き出しで書き出しを行うアセットのパス配列
     *
     * @return array
     */
    public function outputAssetPaths(): array;
}
