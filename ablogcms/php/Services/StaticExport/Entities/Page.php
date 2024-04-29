<?php

namespace Acms\Services\StaticExport\Entities;

class Page
{
    /**
     * ページのURL
     * @var string
     */
    private $url;

    /**
     * 出力先ファイルのパス
     * @var string
     */
    private $destinationPathname;

    public function __construct(string $url, string $destinationPathname)
    {
        $this->url = $url;
        $this->destinationPathname = $destinationPathname;
    }

    /**
     * ページのURLを取得
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * 出力先ファイルのファイル名を含むパスを取得
     */
    public function getDestinationPathname(): string
    {
        return $this->destinationPathname;
    }
}
