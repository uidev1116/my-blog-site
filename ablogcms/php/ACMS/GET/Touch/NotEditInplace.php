<?php

use Acms\Services\Facades\Entry;

class ACMS_GET_Touch_NotEditInplace extends ACMS_GET
{
    public function get()
    {
        if (!Entry::canUseDirectEdit()) {
            // ダイレクト編集が利用できないユーザーの場合は表示しない
            return '';
        }
        if (Entry::isDirectEditEnabled()) {
            // ダイレクト編集が有効の場合は表示しない
            return '';
        }
        return $this->tpl;
    }
}
