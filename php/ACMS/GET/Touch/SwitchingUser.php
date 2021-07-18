<?php

class ACMS_GET_Touch_SwitchingUser extends ACMS_GET
{
    function get()
    {
        if ($this->getOriginalUserId()) {
            return $this->tpl;
        }
        return false;
    }

    /**
     * Get original user id.
     *
     * @return bool
     */
    protected function getOriginalUserId()
    {
        DB::setThrowException(true);
        try {
            $SQL = SQL::newSelect('session');
            $SQL->setSelect('session_original_user_id');
            $SQL->addWhereOpr('session_id', ACMS_SID);
            if ($uid = DB::query($SQL->get(dsn()), 'one')) {
                return $uid;
            }
        } catch (\Exception $e) {

        }
        DB::setThrowException(false);
        return false;
    }
}
