<?php

use Acms\Services\Facades\Media;

class ACMS_POST_Media_Tag_Update extends ACMS_POST
{
    public function post()
    {
        $tag = $this->Post->get('tag');

        $this->Post->setMethod('tag', 'required');

        if (roleAvailableUser()) {
            $this->Post->setMethod(
                'tag',
                'operable',
                !!$this->Q->get('tag') and roleAuthorization('tag_edit', BID)
            );
        } else {
            $this->Post->setMethod(
                'tag',
                'operable',
                !!$this->Q->get('tag') and sessionWithCompilation()
            );
        }
        $this->Post->setMeta('old_tag', 'required');
        if (isReserved($tag)) {
            $this->Post->setMethod('tag', 'reserved', false);
        }
        if (!preg_match(REGEX_INVALID_TAG_NAME, $tag)) {
            $this->Post->setMethod('tag', 'string', false);
        }

        $this->Post->validate(new ACMS_Validator());

        if ($this->Post->isValidAll()) {
            $newTag = $this->Post->get('tag');
            $oldTag = $this->Post->get('old_tag');
            Media::updateTag($oldTag, $newTag);
            $this->Post->set('edit', 'update');
        }
        return $this->Post;
    }
}
