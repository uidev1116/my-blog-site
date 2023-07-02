<?php

class ACMS_POST_Entry extends ACMS_POST
{
    /**
     * @param $Entry
     * @return bool
     */
    function fix($Entry)
    {
        // strtotimeは、半角スペースを渡すとタイムスタンプを返し、空文字であればfalseを返す
        // dateとtimeを連結した際に，半角スペースしか残らなければ、正しくfalseにするため空文字に置き換える
        $strDt = ' ' !== ($strDt = $Entry->get('date').' '.$Entry->get('time')) ? $strDt : '';
        $strStartDt = ' ' !== ($strStartDt = $Entry->get('start_date').' '.$Entry->get('start_time')) ? $strStartDt : '';
        $strEndDt = ' ' !== ($strEndDt = $Entry->get('end_date').' '.$Entry->get('end_time')) ? $strEndDt   : '';

        //----------
        // datetime
        if ( false !== ($dt = strtotime($strDt)) ) {

            $Entry->setField('date', date('Y-m-d', $dt));
            $Entry->setField('time', date('H:i:s', $dt));
        } else {
            $Entry->setField('date', date('Y-m-d', REQUEST_TIME));
            $Entry->setField('time', date('H:i:s', REQUEST_TIME));
        }
        if ( false !== ($dt = strtotime($strStartDt)) ) {
            $Entry->setField('start_date', date('Y-m-d', $dt));
            $Entry->setField('start_time', date('H:i:s', $dt));
        } else {
            $Entry->setField('start_date', '1000-01-01');
            $Entry->setField('start_time', '00:00:00');
        }
        if ( false !== ($dt = strtotime($strEndDt)) ) {
            $Entry->setField('end_date', date('Y-m-d', $dt));
            $Entry->setField('end_time', date('H:i:s', $dt));
        } else {
            $Entry->setField('end_date', '9999-12-31');
            $Entry->setField('end_time', '23:59:59');
        }

        return true;
    }

    function clearCache($bid, $eid)
    {
        // clear page cache
        if (config('cache_clear_when_post') !== 'on') {
            ACMS_POST_Cache::clearEntryPageCache($eid); // このエントリのみ削除
        } else {
            ACMS_POST_Cache::clearPageCache($bid);
        }
    }

    /**
     * @param $url
     * @param bool $ajax
     */
    function responseRedirect($url, $ajax=false)
    {
        if ($ajax) {
            die(json_encode(array(
                'action' => 'redirect',
                'url' => $url,
            )));
        } else {
            $this->redirect($url);
        }
    }

    /**
     * @param bool $ajax
     * @return Field
     */
    function responseGet($ajax=false)
    {
        if ($ajax) {
            die(json_encode(array(
                'action' => 'post',
                'throughPost' => acmsSerialize($this->Post),
            )));
        }
        return $this->Post;
    }

    /**
     * ToDo: deprecated method 2.7.0
     */
    function validEntryCodeDouble($code, $bid=BID, $cid=null, $eid=null)
    {
        return Entry::validEntryCodeDouble($code, $bid, $cid, $eid);
    }

    /**
     * ToDo: deprecated method 2.7.0
     */
    function pingTrackback($endpoint, $eid)
    {
        Entry::pingTrackback($endpoint, $eid);
    }

    /**
     * ToDo: deprecated method 2.7.0
     */
    function entryArchivesDelete($eid)
    {
        Entry::entryArchivesDelete($eid);
    }

    /**
     * ToDo: deprecated method 2.7.0
     */
    function entryDelete($eid)
    {
        Entry::entryDelete($eid);
        return true;
    }

    /**
     * ToDo: deprecated method 2.7.0
     */
    function revisionDelete($eid)
    {
        Entry::revisionDelete($eid);
        return true;
    }

    /**
     * ToDo: deprecated method 2.7.0
     */
    function changeRevision($rvid, $eid, $bid)
    {
        return Entry::changeRevision($rvid, $eid, $bid);
    }

    /**
     * ToDo: deprecated method 2.7.0
     */
    function extractColumn(& $summaryRange=null, $olddel=true, $directAdd=false, $moveArchive='')
    {
        $unit = Entry::extractColumn($summaryRange, $olddel, $directAdd, $moveArchive);
        $summaryRange = Entry::getSummaryRange();

        return $unit;
    }

    /**
     * ToDo: deprecated method 2.7.0
     */
    function saveColumn(& $Column, $eid, $bid, $add=false, $rvid=null, $moveArchive=false)
    {
        $main_image_id = Entry::saveColumn($Column, $eid, $bid, $add, $rvid, $moveArchive);
        $Column = Entry::getSavedColumn();

        return $main_image_id;
    }

    /**
     * ToDo: deprecated method 2.7.0
     */
    function saveRelatedEntries($eid, $entryAry=array(), $rvid=null)
    {
        Entry::saveRelatedEntries($eid, $entryAry, $rvid);
    }

    /**
     * ToDo: deprecated method 2.7.0
     */
    function saveEntryRevision($eid, $entryAry, $type=null, $memo='')
    {
        return Entry::saveEntryRevision($eid, $entryAry, $type, $memo);
    }

    /**
     * ToDo: deprecated method 2.7.0
     */
    function saveUnitRevision(& $Unit, $eid, $bid, $rvid, $moveArchive=false)
    {
        $main_image_id = Entry::saveUnitRevision($Unit, $eid, $bid, $rvid, $moveArchive);
        $Unit = Entry::getSavedColumn();

        return $main_image_id;
    }

    /**
     * ToDo: deprecated method 2.7.0
     */
    function saveFieldRevision($eid, $Field, $rvid, $moveFieldArchive=false)
    {
        return Entry::saveFieldRevision($eid, $Field, $rvid, $moveFieldArchive);
    }

    /**
     * ToDo: deprecated method 2.7.0
     */
    function updateCacheControl($start, $end, $bid=BID, $eid=EID)
    {
        return Entry::updateCacheControl($start, $end, $bid, $eid);
    }

    /**
     * ToDo: deprecated method 2.7.0
     */
    function deleteCacheControl($eid=EID)
    {
        return Entry::deleteCacheControl($eid);
    }

    /**
     * @param \Field $input
     * @param string $datetime
     * @return string
     */
    protected function getFixPublicDate($input, $datetime)
    {
        if (config('entry_edit_start_date_equal_with_entry') === 'on') {
            return $datetime;
        }
        return $input->get('start_date') . ' ' . $input->get('start_time');
    }
}
