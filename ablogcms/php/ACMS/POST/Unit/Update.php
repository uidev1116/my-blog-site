<?php

class ACMS_POST_Unit_Update extends ACMS_POST_Unit
{
    function post()
    {
        $bid    = $this->Post->get('bid');
        $eid    = $this->Post->get('eid');
        $entry  = ACMS_RAM::entry($eid);

        if (!roleEntryUpdateAuthorization(BID, $entry)) die();

        $Column = Entry::extractColumn();
        $Res = Entry::saveColumn($Column, $eid, $bid, true);

        $primaryImageId_p   = $this->Post->get('primary_image');
        $primaryImageId     = empty($Res) ? null : (
            !UTID ? reset($Res) : (
                !empty($Res[UTID]) ? $Res[UTID] : reset($Res)
            )
        );

        if ( intval($primaryImageId) > 0 && intval($primaryImageId_p) === intval($primaryImageId) ) {
            $DB     = DB::singleton(dsn());
            $SQL    = SQL::newUpdate('entry');
            $SQL->addUpdate('entry_primary_image', $primaryImageId);
            $SQL->addWhereOpr('entry_id', EID);
            $SQL->addWhereOpr('entry_blog_id', BID);
            $DB->query($SQL->get(dsn()), 'exec');
            ACMS_RAM::entry(EID, null);
        }

        Common::saveFulltext('eid', $eid, Common::loadEntryFulltext($eid));
        $this->fixEntry($eid);

        //------
        // Hook
        if (HOOK_ENABLE) {
            $Hook = ACMS_Hook::singleton();
            $Hook->call('saveEntry', array(EID, 1));
            Webhook::call(BID, 'entry', 'entry:updated', array(EID, null));
        }

        AcmsLogger::info('「' . ACMS_RAM::entryTitle(EID) . '」エントリーのユニットを更新しました', $Column);

        return $this->Post;
    }
}
