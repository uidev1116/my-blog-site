<?php

use Acms\Services\Facades\Media;

class ACMS_POST_Media_Tag_Update extends ACMS_POST
{
    public function post()
    {
        $tag = $this->Post->get('tag');

        if (roleAvailableUser()) {
            $this->Post->setMethod('tag', 'operable', roleAuthorization('tag_edit', BID));
        } else {
            $this->Post->setMethod('tag', 'operable', sessionWithCompilation());
        }
        $this->Post->setMethod('tag', 'required');
        $this->Post->setMethod('old_tag', 'required');

        if (isReserved($tag)) {
            $this->Post->setMethod('tag', 'reserved', false);
        }
        if (!preg_match(REGEX_INVALID_TAG_NAME, $tag)) {
            $this->Post->setMethod('tag', 'string', false);
        }

        $this->Post->validate(new ACMS_Validator());

        $newTag = $this->Post->get('tag');
        $oldTag = $this->Post->get('old_tag');

        if ($this->Post->isValidAll()) {
            Media::updateTag($oldTag, $newTag);
            $this->Post->set('edit', 'update');

            AcmsLogger::info('メディアのタグ名を更新しました', [
                'old' => $oldTag,
                'new' => $newTag,
            ]);
        } else {
            if (!$this->Post->isValid('tag', 'operable')) {
                AcmsLogger::info('権限がないため、メディアのタグ名を更新できませんでした', [
                    'old' => $oldTag,
                    'new' => $newTag,
                ]);
            }
            if (!$this->Post->isValid('tag', 'required')) {
                AcmsLogger::info('変更後のタグ名が指定されていないため、メディアのタグ名を更新できませんでした', [
                    'old' => $oldTag,
                ]);
            }
            if (!$this->Post->isValid('old_tag', 'required')) {
                AcmsLogger::info('変更前のタグ名が指定されていないため、メディアのタグ名を更新できませんでした', [
                    'new' => $newTag,
                ]);
            }
            if (!$this->Post->isValid('tag', 'reserved')) {
                AcmsLogger::info('予約ワードのため、メディアのタグ名を更新できませんでした', [
                    'old' => $oldTag,
                    'new' => $newTag,
                ]);
            }
            if (!$this->Post->isValid('tag', 'string')) {
                AcmsLogger::info('不正なフォーマットのため、メディアのタグ名を更新できませんでした', [
                    'old' => $oldTag,
                    'new' => $newTag,
                ]);
            }
        }
        return $this->Post;
    }
}
