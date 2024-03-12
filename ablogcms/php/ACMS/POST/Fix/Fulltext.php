<?php

class ACMS_POST_Fix_Fulltext extends ACMS_POST_Fix
{
    function post()
    {
        if (!sessionWithAdministration()) {
            return false;
        }

        $Fix = $this->extract('fix', new ACMS_Validator());
        $Fix->setMethod('fix_fulltext_targeet', 'required');

        if ($this->Post->isValidAll()) {
            @set_time_limit(0);
            $DB = DB::singleton(dsn());
            $type = $Fix->get('fix_fulltext_targeet');

            if ($type === 'blog') {
                Common::saveFulltext('bid', BID, Common::loadBlogFulltext(BID));
            } else {
                $SQL = SQL::newSelect($type);
                $SQL->addSelect($type . '_id');
                $SQL->addWhereOpr($type . '_blog_id', BID);
                $all = $DB->query($SQL->get(dsn()), 'all');

                foreach ($all as $row) {
                    $id = $row[$type . '_id'];
                    switch ($type) {
                        case 'category':
                            Common::saveFulltext('cid', $id, Common::loadCategoryFulltext($id));
                            break;
                        case 'user':
                            Common::saveFulltext('uid', $id, Common::loadUserFulltext($id));
                            break;
                        case 'entry':
                            Common::saveFulltext('eid', $id, Common::loadEntryFulltext($id));
                            break;
                    }
                }
            }
            $this->Post->set('message', 'success');

            $typeName = '';
            if ($type === 'blog') {
                $typeName = 'ブログ';
            }
            if ($type === 'category') {
                $typeName = 'カテゴリー';
            }
            if ($type === 'user') {
                $typeName = 'ユーザー';
            }
            if ($type === 'entry') {
                $typeName = 'エントリー';
            }

            AcmsLogger::info('「' . $typeName . '」のフルテキストを修正しました');
        }

        return $this->Post;
    }
}
