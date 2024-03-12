<?php

class ACMS_POST_Entry_Lock_Unlock extends ACMS_POST_Entry
{
    public function post()
    {
        $eid = intval($this->Post->get('eid'));

        try {
            $service = App::make('entry.lock');
            $service->unlock($eid, 0);

            $this->addMessage('「' . ACMS_RAM::entryTitle($eid) . '」のロックを解除しました');
        } catch (Exception $e) {
        }
        return $this->Post;
    }
}
