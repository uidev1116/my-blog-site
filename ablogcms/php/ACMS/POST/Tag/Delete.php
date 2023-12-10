<?php

class ACMS_POST_Tag_Delete extends ACMS_POST
{
    function post()
    {
        if (roleAvailableUser()) {
            $this->Post->setMethod(
                'tag',
                'operable',
                !!$this->Q->get('tag') && roleAuthorization('tag_edit', BID)
            );
        } else {
            $this->Post->setMethod(
                'tag',
                'operable',
                !!$this->Q->get('tag') && sessionWithCompilation()
            );
        }
        $this->Post->validate();

        if ($this->Post->isValidAll()) {
            $name = $this->Q->get('tag');
            $DB = DB::singleton(dsn());
            $SQL = SQL::newDelete('tag');
            $SQL->addWhereOpr('tag_name', $name);
            $SQL->addWhereOpr('tag_blog_id', BID);
            $DB->query($SQL->get(dsn()), 'exec');

            $this->Post->set('edit', 'delete');
            AcmsLogger::info('「' . $name . '」タグを削除しました');
        }
        return $this->Post;
    }
}
