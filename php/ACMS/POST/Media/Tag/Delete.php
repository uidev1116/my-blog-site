<?php

use Acms\Services\Facades\Media;

class ACMS_POST_Media_Tag_Delete extends ACMS_POST
{
    function post()
    {
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
        $this->Post->validate();

        if ($this->Post->isValidAll()) {
            $tagName = $this->Q->get('tag');
            Media::deleteTag($tagName);
            $this->Post->set('edit', 'delete');
        }

        return $this->Post;
    }
}
