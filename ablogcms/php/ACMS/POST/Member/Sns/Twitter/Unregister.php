<?php

class ACMS_POST_Member_Sns_Twitter_Unregister extends ACMS_POST_Member
{
    /**
     * Main
     *
     * @return Field_Validation
     */
    public function post(): Field_Validation
    {
        if (!SUID) {
            return $this->Post;
        }
        $SQL = SQL::newUpdate('user');
        $SQL->addUpdate('user_twitter_id', '');
        $SQL->addWhereOpr('user_id', SUID);
        DB::query($SQL->get(dsn()), 'exec');
        ACMS_RAM::cacheDelete();
        ACMS_RAM::user(SUID, null);

        $session = Session::handle();
        $session->set('oauth-unregister', 'success');
        $session->save();

        AcmsLogger::info('Twitter認証を解除しました');

        return $this->Post;
    }
}
