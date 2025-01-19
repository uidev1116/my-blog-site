<?php

namespace Acms\Services\Unit\Contracts;

interface ValidatePath
{
    /**
     * ファイルのパスを配列で取得
     *
     * @return array
     */
    public function getFilePaths(): array;
}
