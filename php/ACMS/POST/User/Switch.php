<?php

class ACMS_POST_User_Switch extends ACMS_POST_User
{
    var $isCacheDelete  = false;

    /**
     * Run
     *
     * @return Field
     */
    function post()
    {
        $targetUid = intval($this->Post->get('uid'));
        if (!$this->validate(SUID, $targetUid)) {
            die('Invalid operation.');
        }
        $this->switchUser(SUID, $targetUid);
        $this->redirect(acmsLink(array(
            'bid' => BID,
            'admin' => 'top',
        ), false));
    }

    /**
     * Switching user
     *
     * @param int $fromUid
     * @param int $toUid
     */
    protected function switchUser($fromUid, $toUid)
    {
        $session = Session::handle();
        $session->set(ACMS_LOGIN_SESSION_UID, $toUid);
        $session->set(ACMS_LOGIN_SESSION_ORGINAL_UIR, $fromUid);
        $session->save();

        $sql = SQL::newDelete('user_session');
        $sql->addWhereOpr('user_session_uid', $toUid);
        if ($host = getCookieHost()) {
            $sql->addWhereOpr('user_session_host', $host);
        }
        DB::query($sql->get(dsn()), 'exec');
    }

    /**
     * Switching original user.
     *
     * @param int $originalUid
     */
    protected function switchOriginalUser($originalUid)
    {
        $session = Session::handle();
        $session->set(ACMS_LOGIN_SESSION_UID, $originalUid);
        $session->delete(ACMS_LOGIN_SESSION_ORGINAL_UIR);
        $session->save();
    }

    /**
     * Get original user id.
     *
     * @return int
     */
    protected function getOriginalUid()
    {
        $session = Session::handle();
        return $session->get(ACMS_LOGIN_SESSION_ORGINAL_UIR);
    }

    /**
     * Validate
     *
     * @param int $fromUid
     * @param int $toUid
     * @return bool
     */
    protected function validate($fromUid, $toUid)
    {
        try {
            if (!sessionWithAdministration()) {
                throw new \RuntimeException('Invalid operation.');
            }
            if (empty($toUid)) {
                throw new \RuntimeException('Invalid operation.');
            }
            if ($toUid == $fromUid) {
                throw new \RuntimeException('Invalid operation.');
            }
            if (!canSwitchUser($toUid)) {
                throw new \RuntimeException('Invalid operation.');
            }
            $fromUidBlog = ACMS_RAM::userBlog($fromUid);
            $toUidBlog = ACMS_RAM::userBlog($toUid);
            $SQL = SQL::newSelect('blog');
            ACMS_Filter::blogTree($SQL, $fromUidBlog, 'descendant-or-self');
            $SQL->addWhereOpr('blog_id', $toUidBlog);
            if (!DB::query($SQL->get(dsn()), 'one')) {
                throw new \RuntimeException('Invalid operation.');
            }
            return true;
        } catch (\Exception $e) {
            $this->addError($e->getMessage());
        }
        return false;
    }
}
