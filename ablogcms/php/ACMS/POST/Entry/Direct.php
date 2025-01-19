<?php

use Acms\Services\Facades\Application;
use Acms\Services\Facades\Database;
use Acms\Services\Facades\Common;

class ACMS_POST_Entry_Direct extends ACMS_POST_Unit
{
    function post()
    {
        $bid = (int) $this->Post->get('bid');
        $eid = (int) $this->Post->get('eid');
        $entry = ACMS_RAM::entry($eid);
        if (!roleEntryUpdateAuthorization(BID, $entry)) {
            die();
        }

        /**  @var \Acms\Services\Unit\Repository $unitRepository */
        $unitRepository = Application::make('unit-repository');

        $sort = $unitRepository->countUnitsTrait(EID) + 1; // @phpstan-ignore-line
        $_POST['sort'][0] = $sort;

        $units = $unitRepository->extractUnits(null);
        $imageUnitIdTable = $unitRepository->saveUnits($units, $eid, $bid, true);

        $unit = $unitRepository->getUnitBySortTrait(EID, $sort); // @phpstan-ignore-line
        $utid = (int) ($unit['column_id'] ?? 0);
        if ($utid) {
            $this->Post->set('utid', $utid);
        } else {
            die();
        }

        $primaryImageId_p = $this->Post->get('primary_image');
        $primaryImageId = empty($imageUnitIdTable) ? null : (!UTID ? reset($imageUnitIdTable) : (!empty($imageUnitIdTable[UTID]) ? $imageUnitIdTable[UTID] : reset($imageUnitIdTable)));

        if (intval($primaryImageId) > 0 && intval($primaryImageId_p) === intval($primaryImageId)) {
            $sql = SQL::newUpdate('entry');
            $sql->addUpdate('entry_primary_image', $primaryImageId);
            $sql->addWhereOpr('entry_id', EID);
            $sql->addWhereOpr('entry_blog_id', BID);
            Database::query($sql->get(dsn()), 'exec');
            ACMS_RAM::entry(EID, null);
        }

        Common::saveFulltext('eid', $eid, Common::loadEntryFulltext($eid));
        $this->fixEntry($eid);

        return $this->Post;
    }
}
