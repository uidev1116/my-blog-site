<?php

class ACMS_POST_Entry_BulkChange extends ACMS_POST
{
    /**
     * CSRF対策
     *
     * @var bool
     */
    protected $isCSRF = true;

    /**
     * 二重送信対策
     *
     * @var bool
     */
    protected $checkDoubleSubmit = true;

    /**
     * Validator
     *
     * @throws ACMS_POST_Entry_BulkChange_Exceptions_PermissionDenied
     */
    protected function validate()
    {
        if (!sessionWithAdministration()) {
            throw new ACMS_POST_Entry_BulkChange_Exceptions_PermissionDenied;
        }
    }
}
