<?php

namespace Acms\Services\Unit\Contracts;

interface ExportEntry
{
    /**
     * エントリーのエクスポートでエクスポートするアセットを返却
     *
     * @return string[]
     */
    public function exportArchivesFiles(): array;

    /**
     * エントリーのエクスポートでエクスポートするメディアIDを返却
     *
     * @return int[]
     */
    public function exportMediaIds(): array;

    /**
     * エントリーのエクスポートでエクスポートするモジュールIDを返却
     *
     * @return int[]
     */
    public function exportModuleIds(): array;
}
