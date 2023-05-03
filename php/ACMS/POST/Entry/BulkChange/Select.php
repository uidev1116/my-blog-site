<?php

class ACMS_POST_Entry_BulkChange_Select extends ACMS_POST_Entry_BulkChange
{
    /**
     * @var array
     */
    protected $eids = array();

    /**
     * Run
     *
     * @return \Field
     */
    function post()
    {
        $this->eids = $this->Post->getArray('checks');
        try {
            $this->validate();
            $this->Post->set('step', '2');
        } catch (ACMS_POST_Entry_BulkChange_Exceptions_PermissionDenied $e) {
            die('Permission denied.');
        } catch (ACMS_POST_Entry_BulkChange_Exceptions_TargetEmpty $e) {
            $this->Post->set('step', '1');
            $this->Post->set('error', 'targetEmpty');
        }
        return $this->Post;
    }

    /**
     * Validator
     *
     * @throws ACMS_POST_Entry_BulkChange_Exceptions_PermissionDenied
     * @throws ACMS_POST_Entry_BulkChange_Exceptions_TargetEmpty
     */
    protected function validate()
    {
        parent::validate();

        if (count($this->eids) < 1) {
            throw new ACMS_POST_Entry_BulkChange_Exceptions_TargetEmpty;
        }
    }
}
