<?php

namespace Acms\Services\StaticExport\Entities;

class EntryPage extends Page
{
    /**
     * ページのエントリーID
     * @var int
     */
    private $entryId;

    public function __construct(
        string $url,
        string $destinationPathname,
        int $entryId
    ) {
        parent::__construct($url, $destinationPathname);
        $this->entryId = $entryId;
    }

    /**
     * ページのエントリーIDを取得
     */
    public function getEntryId(): int
    {
        return $this->entryId;
    }
}
