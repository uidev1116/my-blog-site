<?php

class ACMS_GET_Admin_Entry_BulkChange extends ACMS_GET_Admin_Entry
{
    protected function validate()
    {
        throw new \RuntimeException('Permission Denied.');
    }
}
