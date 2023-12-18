<?php

class ACMS_POST_Comment_Index_Delete extends ACMS_POST_Comment
{
    function post()
    {
        $this->Post->setMethod('comment', 'operative', sessionWithCompilation());
        $this->Post->validate(new ACMS_Validator());

        if ($this->Post->isValidAll()) {
            $DB = DB::singleton(dsn());
            $targetCMIDs = [];
            foreach ($this->Post->getArray('checks') as $cmid) {
                $SQL    = SQL::newDelete('comment');
                $SQL->addWhereOpr('comment_id', intval($cmid));
                $SQL->addWhereOpr('comment_blog_id', BID);
                $DB->query($SQL->get(dsn()), 'exec');

                $targetCMIDs[] = $cmid;
            }
            AcmsLogger::info('選択されたコメントの削除しました', [
                'targetCMIDs' => implode(',', $targetCMIDs),
            ]);
        }

        return $this->Post;
    }
}
