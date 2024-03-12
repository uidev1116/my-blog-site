<?php

class ACMS_POST_Entry_Index_Sort extends ACMS_POST
{
    public $sortField  = null;

    function post()
    {
        $this->Post->setMethod(
            'entry',
            'operative',
            ('entry_user_sort' == $this->sortField) ?
            sessionWithContribution() :
            ( sessionWithCompilation() || roleAuthorization('entry_edit_all') )
        );
        $this->Post->setMethod('checks', 'required');
        $this->Post->validate(new ACMS_Validator());

        if ($this->Post->isValidAll()) {
            $DB = DB::singleton(dsn());
            $targetEIDs = [];
            foreach ($this->Post->getArray('checks') as $eid) {
                $id     = preg_split('@:@', $eid, 2, PREG_SPLIT_NO_EMPTY);
                $bid    = $id[0];
                $eid    = $id[1];
                if (!($eid = intval($eid))) {
                    continue;
                }
                if (!($bid = intval($bid))) {
                    continue;
                }
                if (!($sort = intval($this->Post->get('sort-' . $eid)))) {
                    $sort = 1;
                }

                $SQL    = SQL::newUpdate('entry');
                $SQL->setUpdate($this->sortField, $sort);
                $SQL->addWhereOpr('entry_id', $eid);
                $SQL->addWhereOpr('entry_blog_id', $bid);
                if (
                    1
                    and 'entry_user_sort' == $this->sortField
                    and ( !sessionWithCompilation() && !roleAuthorization('entry_edit_all') )
                ) {
                    $SQL->addWhereOpr('entry_user_id', SUID);
                }
                $DB->query($SQL->get(dsn()), 'exec');
                ACMS_RAM::entry($eid, null);
                $targetEIDs[] = $eid;
            }
            AcmsLogger::info('指定されたエントリーの並び順を変更しました', [
                'targetEIDs' => $targetEIDs,
            ]);
        } else {
            AcmsLogger::info('指定されたエントリーの並び順変更に失敗しました');
        }

        return $this->Post;
    }
}
