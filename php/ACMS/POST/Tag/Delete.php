<?php

class ACMS_POST_Tag_Delete extends ACMS_POST
{
    function post()
    {
        if ( roleAvailableUser() ) {
            $this->Post->setMethod('tag', 'operable', 
                !!$this->Q->get('tag') and roleAuthorization('tag_edit', BID)
            );
        } else {
            $this->Post->setMethod('tag', 'operable', 
                !!$this->Q->get('tag') and sessionWithCompilation()
            );
        }
        $this->Post->validate();

        if ( $this->Post->isValidAll() ) {
            $DB     = DB::singleton(dsn());
            $SQL    = SQL::newDelete('tag');
            $SQL->addWhereOpr('tag_name', $this->Q->get('tag'));
            $SQL->addWhereOpr('tag_blog_id', BID);
            $DB->query($SQL->get(dsn()), 'exec');

            $this->Post->set('edit', 'delete');
        }

        return $this->Post;
    }
}
