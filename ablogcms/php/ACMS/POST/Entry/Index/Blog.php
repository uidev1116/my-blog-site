<?php

class ACMS_POST_Entry_Index_Blog extends ACMS_POST
{
    function post()
    {
        if ( !($bid = intval($this->Post->get('bid'))) ) $bid = null;
        $this->Post->setMethod('checks', 'required');
        if ( enableApproval($bid, null) ) {
            $this->Post->setMethod('entry', 'operable', sessionWithApprovalAdministrator($bid, null));
        } else if ( roleAvailableUser() ) {
            $this->Post->setMethod('entry', 'operable', roleAuthorization('admin_etc', $bid));
        } else {
            $this->Post->setMethod('entry', 'operable', sessionWithAdministration($bid));
        }

        $this->Post->validate(new ACMS_Validator());

        if ( $this->Post->isValidAll() ) {
            @set_time_limit(0);
            $DB     = DB::singleton(dsn());

            // エントリーの最大ソート番号
            $Sort   = SQL::newSelect('entry');
            $Sort->addSelect('entry_sort', 'sort_max', null, 'max');
            $Sort->addWhereOpr('entry_blog_id', $bid);
            $sort   = $DB->query($Sort->get(dsn()), 'one');

            // エントリーの最大ソート番号（ユーザー）
            $Usort  = SQL::newSelect('entry');
            $Usort->addSelect('entry_user_sort', 'usort_max', null, 'max');
            $Usort->addWhereOpr('entry_user_id', SUID);
            $Usort->addWhereOpr('entry_blog_id', $bid);
            $usort  = $DB->query($Usort->get(dsn()), 'one');

            // 移動先で参照可能なグローバルカテゴリーを取得する
            $SQL = SQL::newSelect('category');
            $SQL->addSelect('category_id', 'id');
            $SQL->addWhereOpr('category_scope', 'global');  // TreeGlobalとは別に、category_scope='global'の条件を与えておく
            $SQL->addLeftJoin('blog', 'blog_id', 'category_blog_id');
            ACMS_Filter::categoryTreeGlobal($SQL, $bid, true);
            $globalCids = array();
            foreach ( $DB->query($SQL->get(dsn()), 'all') as $category ) {
                $globalCids[] = $category['id'];
            }

            $cidSorts = array();
            foreach ( array_merge(array(null), $globalCids) as $cid ) {
                /** @var int|null $cid */

                // そのカテゴリー（ないしnull）最大ソート番号の現在値を取得
                $Csort  = SQL::newSelect('entry');
                $Csort->addSelect('entry_category_sort', 'csort_max', null, 'max');
                $Csort->addWhereOpr('entry_category_id', $cid);
                $Csort->addWhereOpr('entry_blog_id', $bid);
                $cmax  = $DB->query($Csort->get(dsn()), 'one');
                if ( empty($cmax) ) {
                    $cmax = 0;
                }
                if ( null === $cid ) {
                    $cid = 0;
                }
                $cidSorts[$cid] = $cmax;
            }

            $targetEIDs = [];
            foreach ( array_reverse($this->Post->getArray('checks')) as $eid ) {
                $id     = preg_split('@:@', $eid, 2, PREG_SPLIT_NO_EMPTY);
                $eid    = $id[1];
                if ( !($eid = intval($eid)) ) continue;
                $cid    = ACMS_RAM::entryCategory($eid);

                $sort++;
                $usort++;
                if ( !isset($cidSorts[$cid]) ) {
                    $cidSorts[$cid] = 0;
                }
                $cidSorts[$cid]++;

                $SQL    = SQL::newUpdate('entry');
                $SQL->addUpdate('entry_blog_id', $bid);
                if ( !in_array($cid, $globalCids) ) {
                    $SQL->addUpdate('entry_category_id', null);
                }
                $SQL->addUpdate('entry_sort', $sort);
                $SQL->addUpdate('entry_user_sort', $usort);
                $SQL->addUpdate('entry_category_sort', $cidSorts[$cid]);

                $SQL->addWhereOpr('entry_id', $eid);
                if ( !sessionWithCompilation() ) {
                    $SQL->addWhereOpr('entry_user_id', SUID);
                }
                $DB->query($SQL->get(dsn()), 'exec');
                ACMS_RAM::entry($eid, null);

                $SQL    = SQL::newUpdate('fulltext');
                $SQL->addUpdate('fulltext_blog_id', $bid);
                $SQL->addWhereOpr('fulltext_eid', $eid);
                $DB->query($SQL->get(dsn()), 'exec');

                $SQL    = SQL::newUpdate('field');
                $SQL->addUpdate('field_blog_id', $bid);
                $SQL->addWhereOpr('field_eid', $eid);
                $DB->query($SQL->get(dsn()), 'exec');
                Common::deleteFieldCache('eid', $eid);

                $SQL    = SQL::newUpdate('column');
                $SQL->addUpdate('column_blog_id', $bid);
                $SQL->addWhereOpr('column_entry_id', $eid);
                $DB->query($SQL->get(dsn()), 'exec');

                $SQL    = SQL::newUpdate('tag');
                $SQL->addUpdate('tag_blog_id', $bid);
                $SQL->addWhereOpr('tag_entry_id', $eid);
                $DB->query($SQL->get(dsn()), 'exec');

                $targetEIDs[] = $eid;
            }
            AcmsLogger::info('指定されたエントリーを「' . ACMS_RAM::blogName($bid) . '」ブログに一括移動させました', [
                'targetEIDs' => implode(',', $targetEIDs),
                'targetBID' => $bid,
            ]);
        } else {
            AcmsLogger::info('指定されたエントリーの一括ブログ移動に失敗しました');
        }

        return $this->Post;
    }
}
