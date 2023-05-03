<?php

class ACMS_POST_User_SwitchOriginal extends ACMS_POST_User_Switch
{
    /**
     * Run
     *
     * @return Field
     */
    function post()
    {
        $originalUid = $this->getOriginalUid();
        if (!$this->validate(SUID, $originalUid)) {
            return $this->Post;
        }
        $this->switchOriginalUser($originalUid);
        $this->redirect(acmsLink(array(
            'bid' => BID,
            'admin' => 'top',
        ), false));
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
            if (empty($toUid)) {
                throw new \RuntimeException('Invalid operation.');
            }
            if ($toUid == $fromUid) {
                throw new \RuntimeException('Invalid operation.');
            }
            return true;
        } catch (\Exception $e) {
            $this->addError($e->getMessage());
        }
        return false;
    }
}