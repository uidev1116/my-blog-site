<?php

class ACMS_POST_Entry_BulkChange_Chain extends ACMS_POST_Entry_BulkChange
{
    public function post()
    {
        $this->Post->reset(true);
        return $this->Post;
    }
}
