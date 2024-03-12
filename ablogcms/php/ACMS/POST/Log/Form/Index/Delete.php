<?php

class ACMS_POST_Log_Form_Index_Delete extends ACMS_POST_Log_Form_Delete
{
    function post()
    {
        $this->Post->reset(true);
        $this->Post->setMethod('checks', 'required');

        $this->Post->setMethod('log', 'fmidIsNull', !!($fmid = intval($this->Get->get('fmid'))));
        if (roleAvailableUser()) {
            $this->Post->setMethod('log', 'operative', roleAuthorization('form_edit', BID));
        } else {
            $this->Post->setMethod('log', 'operative', sessionWithAdministration());
        }
        $this->Post->validate(new ACMS_Validator());

        if ($this->Post->isValidAll()) {
            @set_time_limit(0);
            $DB     = DB::singleton(dsn());
            foreach ($this->Post->getArray('checks') as $form_log_id) {
                $id     = preg_split('@:@', $form_log_id, 2, PREG_SPLIT_NO_EMPTY);
                $fmid   = $id[0];
                $sid    = $id[1];
                $to     = isset($id[2]) ? $id[2] : null;

                $this->delete($fmid, $sid, $to);
            }
        }

        return $this->Post;
    }
}
