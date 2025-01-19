<?php

use Acms\Services\Facades\Application;
use Acms\Services\Facades\Entry;
use Acms\Services\Facades\Database as DB;
use Acms\Services\Facades\Logger as AcmsLogger;

class ACMS_POST_Revision_Duplicate extends ACMS_POST_Entry
{
    use \Acms\Traits\Common\AssetsTrait;

    public function post()
    {
        try {
            if (roleAvailableUser()) {
                if (!roleAuthorization('entry_edit', BID, EID)) {
                    throw new \RuntimeException('権限がありません');
                }
            } else {
                if (!sessionWithCompilation(BID)) {
                    if (!sessionWithContribution(BID)) {
                        throw new \RuntimeException('権限がありません');
                    }
                    if (SUID <> ACMS_RAM::entryUser(EID) && !enableApproval(BID, CID)) {
                        throw new \RuntimeException('権限がありません');
                    }
                }
            }
            $DB = DB::singleton(dsn());

            // 新規リビジョン番号取得
            $SQL = SQL::newSelect('entry_rev');
            $SQL->addSelect('entry_rev_id', 'max_rev_id', null, 'MAX');
            $SQL->addWhereOpr('entry_id', EID);
            $SQL->addWhereOpr('entry_blog_id', BID);

            $rvid = 2;
            if ($max = $DB->query($SQL->get(dsn()), 'one')) {
                $rvid = $max + 1;
            }

            $revisionName = $this->Post->get('revisionName');
            if (empty($revisionName)) {
                $revisionName = sprintf(config('revision_default_memo'), $rvid);
            }

            $this->entryDupe($rvid, $revisionName);
            $this->subCategoryDupe($rvid);
            $this->unitDupe($rvid);
            $this->fieldDupe($rvid);
            $this->tagsDupe($rvid);
            $this->relationDupe($rvid);

            AcmsLogger::info('「' . ACMS_RAM::entryTitle(EID) . '」エントリの作業領域からバージョンを作成しました', [
                'eid' => EID,
                'rvid' => $rvid,
                'versionName' => $revisionName,
            ]);

            if ($this->Post->get('redirect', '') == 'approval') {
                $this->redirect(acmsLink([
                    'bid'   => BID,
                    'eid'   => EID,
                    'tpl'   => 'ajax/revision-preview.html',
                    'query' => [
                        'rvid'  => $rvid,
                    ],
                ]));
            } else {
                $this->redirect(acmsLink([
                    'bid'   => BID,
                    'eid'   => EID,
                    'tpl'   => 'ajax/revision-index-list.html',
                ]));
            }
        } catch (\Exception $e) {
            AcmsLogger::info('「' . ACMS_RAM::entryTitle(EID) . '」エントリの作業領域からバージョンを作成できませんでした。' . $e->getMessage(), Common::exceptionArray($e));
            return $this->Post;
        }
    }

    protected function entryDupe($rvid, $revisionName)
    {
        $DB     = DB::singleton(dsn());

        $SQL    = SQL::newSelect('entry_rev');
        $SQL->addWhereOpr('entry_id', EID);
        $SQL->addWhereOpr('entry_rev_id', 1);
        $SQL->addWhereOpr('entry_blog_id', BID);
        $q      = $SQL->get(dsn());

        $Entry  = SQL::newInsert('entry_rev');
        if ($row = $DB->query($q, 'row')) {
            foreach ($row as $key => $val) {
                if (
                    !in_array($key, [
                        'entry_rev_id',
                        'entry_rev_status',
                        'entry_rev_user_id',
                        'entry_rev_datetime',
                        'entry_rev_memo'
                    ], true)
                ) {
                    $Entry->addInsert($key, $val);
                }
            }
            $Entry->addInsert('entry_rev_id', $rvid);
            $Entry->addInsert('entry_rev_status', 'none');
            $Entry->addInsert('entry_rev_user_id', SUID);
            $Entry->addInsert('entry_rev_datetime', date('Y-m-d H:i:s', REQUEST_TIME));
            $Entry->addInsert('entry_rev_memo', $revisionName);
            $DB->query($Entry->get(dsn()), 'exec');
        }
    }

    function subCategoryDupe($rvid)
    {
        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('entry_sub_category_rev');
        $SQL->addWhereOpr('entry_sub_category_eid', EID);
        $SQL->addWhereOpr('entry_sub_category_rev_id', 1);
        $SQL->addWhereOpr('entry_sub_category_blog_id', BID);
        $q = $SQL->get(dsn());

        $SubCategory = SQL::newInsert('entry_sub_category_rev');
        if ($DB->query($q, 'fetch') and ($row = $DB->fetch($q))) {
            do {
                foreach ($row as $key => $val) {
                    if ($key !== 'entry_sub_category_rev_id') {
                        $SubCategory->addInsert($key, $val);
                    }
                }
                $SubCategory->addInsert('entry_sub_category_rev_id', $rvid);
                $DB->query($SubCategory->get(dsn()), 'exec');
            } while ($row = $DB->fetch($q));
        }
    }

    protected function unitDupe($rvid)
    {
        // ユニットを複製
        /** @var \Acms\Services\Unit\Repository $unitRepository */
        $unitRepository = Application::make('unit-repository');
        $unitRepository->duplicateRevisionUnits(EID, 1, $rvid); // @phpstan-ignore-line
    }

    protected function fieldDupe($rvid)
    {
        $revisionField  = loadEntryField(EID, 1);
        $this->duplicateFieldsTrait($revisionField);
        Entry::saveFieldRevision(EID, $revisionField, $rvid);
    }

    protected function tagsDupe($rvid)
    {
        $DB     = DB::singleton(dsn());

        $SQL    = SQL::newSelect('tag_rev');
        $SQL->addWhereOpr('tag_entry_id', EID);
        $SQL->addWhereOpr('tag_rev_id', 1);
        $SQL->addWhereOpr('tag_blog_id', BID);
        $q      = $SQL->get(dsn());

        $Tag    = SQL::newInsert('tag_rev');
        if ($DB->query($q, 'fetch') and ($row = $DB->fetch($q))) {
            do {
                foreach ($row as $key => $val) {
                    if ($key !== 'tag_rev_id') {
                        $Tag->addInsert($key, $val);
                    }
                }
                $Tag->addInsert('tag_rev_id', $rvid);
                $DB->query($Tag->get(dsn()), 'exec');
            } while ($row = $DB->fetch($q));
        }
    }

    function relationDupe($rvid)
    {
        $DB     = DB::singleton(dsn());

        $SQL    = SQL::newSelect('relationship_rev');
        $SQL->addWhereOpr('relation_id', EID);
        $SQL->addWhereOpr('relation_rev_id', 1);
        $all    = $DB->query($SQL->get(dsn()), 'all');

        foreach ($all as $row) {
            $SQL = SQL::newInsert('relationship_rev');
            $SQL->addInsert('relation_id', $row['relation_id']);
            $SQL->addInsert('relation_rev_id', $rvid);
            $SQL->addInsert('relation_eid', $row['relation_eid']);
            $SQL->addInsert('relation_type', $row['relation_type']);
            $SQL->addInsert('relation_order', $row['relation_order']);
            $DB->query($SQL->get(dsn()), 'exec');
        }
    }
}
