<?php

use Acms\Services\Facades\Media;

class ACMS_POST_Media_Tag_Delete extends ACMS_POST
{
    function post()
    {
        if (roleAvailableUser()) {
            $this->Post->setMethod('tag', 'operable', roleAuthorization('tag_edit', BID));
        } else {
            $this->Post->setMethod('tag', 'operable', sessionWithCompilation());
        }
        if (!$this->Q->get('tag')) {
            $this->Post->setMethod('tag', 'required', false);
        }
        $this->Post->validate();

        $tagName = $this->Q->get('tag');

        if ($this->Post->isValidAll()) {
            Media::deleteTag($tagName);
            $this->Post->set('edit', 'delete');

            AcmsLogger::info('メディアタグを削除しました', [
                'tag' => $tagName,
            ]);
        } else {
            if (!$this->Post->isValid('tag', 'operable')) {
                AcmsLogger::info('権限がないため、メディアタグを削除できませんでした', [
                    'tag' => $tagName,
                ]);
            }
            if (!$this->Post->isValid('tag', 'required')) {
                AcmsLogger::info('タグが指定されていないため、メディアタグを削除できませんでした');
            }
        }

        return $this->Post;
    }
}
