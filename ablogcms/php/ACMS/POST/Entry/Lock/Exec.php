<?php

class ACMS_POST_Entry_Lock_Exec extends ACMS_POST_Entry
{
    public function post()
    {
        $eid = intval($this->Post->get('eid'));
        $rvid = intval($this->Post->get('rvid', 0));

        try {
            $service = App::make('entry.lock');
            $service->lock($eid, $rvid, SUID);

            Common::responseJson([
                'locked' => true,
            ]);
        } catch (Exception $e) {
            Common::responseJson([
                'locked' => false,
                'message' => $e->getMessage(),
            ]);
        }
        Common::responseJson([
            'locked' => false,
            'message' => 'Unknown error',
        ]);
    }
}
