<?php

class ACMS_GET_Unit_Fetch extends ACMS_GET_Unit
{
    public function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        $utid           = (int)$this->Post->get('utid', UTID);
        $ary_utid       = array_map('intval', $this->Post->getArray('utid'));
        $eid            = $this->Post->get('eid', EID);
        $renderGroup    = $this->Post->get('renderGroup', 'off');
        $renderGroup    = ($renderGroup === 'on') ? true : false;

        $seeked     = false;
        $preAlign   = null;

        // if Add
        if (empty($utid)) {
            $sort = $this->Get->get('sort');
            $DB = DB::singleton(dsn());
            $SQL = SQL::newSelect('column');
            $SQL->addSelect('column_id');
            $SQL->addWhereOpr('column_sort', $sort);
            $SQL->addWhereOpr('column_entry_id', $eid);
            $utid = $DB->query($SQL->get(dsn()), 'one');
        }

        if ($Column = array_reverse(loadColumn($eid))) {
            foreach ($Column as $i => $row) {
                if ($seeked !== false) {
                    $preAlign = $row['align'];
                    $seeked   = false;
                }
                if (is_array($ary_utid) && (count($ary_utid) > 0)) {
                    if (in_array($row['clid'], $ary_utid, true) === false) {
                        unset($Column[$i]);
                    } else {
                        $seeked = true;
                    }
                } else {
                    if ($row['clid'] != $utid) {
                        unset($Column[$i]);
                    } else {
                        $seeked = true;
                    }
                }
            }
            $Column = array_reverse($Column);
            $this->buildUnit($Column, $Tpl, $eid, $preAlign, $renderGroup);
        }
        return $Tpl->get();
    }
}
