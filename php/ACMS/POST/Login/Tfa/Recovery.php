<?php

class ACMS_POST_Login_Tfa_Recovery extends ACMS_POST_Login
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
     * @return false|Field
     */
    function post()
    {
        $DB = DB::singleton(dsn());
        $Login = $this->extract('login', new ACMS_Validator());

        $inputMail = preg_replace("/(\s|　)/", "", $Login->get('mail'));
        $inputPassword = preg_replace("/(\s|　)/", "", $Login->get('pass'));
        $recoveryCode = preg_replace("/(\s|　)/", "", $Login->get('recovery'));
        $lockKey = md5('Login_Tfa_Recovery' . $inputMail);

        //-------------------
        // access restricted
        if (SUID OR !accessRestricted()) {
            $Login->setMethod('pass', 'auth', false);
        }

        //------
        // CSRF
        if (isCSRF()) {
            $Login->setMethod('pass', 'auth', false);
        }

        //---------
        // 連続試行
        if ($inputMail) {
            $trialTime = intval(config('login_trial_time', 5));
            $trialNumber = intval(config('login_trial_number', 5));
            $lockTime = intval(config('login_lock_time', 5));
            $lock = Common::validateLockPost($lockKey, $trialTime, $trialNumber, $lockTime);
            if ($lock === false) {
                $Login->setMethod('mail', 'lock', false);
            }
        }

        $Login->validate(new ACMS_Validator());
        if (!$this->Post->isValidAll()) {
            return $this->Post;
        }
        $all = array();
        $row = null;

        //----------------
        // authentication
        if (1
            and $inputMail
            and $inputPassword
        ) {
            $SQL = SQL::newSelect('user');
            $SQL->addWhereOpr('user_status', 'open');
            $codeOrMail = SQL::newWhere();
            $codeOrMail->addWhereOpr('user_code', $inputMail, '=', 'OR');
            $codeOrMail->addWhereOpr('user_mail', $inputMail, '=', 'OR');
            $SQL->addWhere($codeOrMail);
            $anywhereOrBid = SQL::newWhere();
            $anywhereOrBid->addWhereOpr('user_login_anywhere', 'on', '=', 'OR');
            $anywhereOrBid->addWhereOpr('user_blog_id', BID, '=', 'OR');
            $SQL->addWhere($anywhereOrBid);
            $SQL->addWhereOpr('user_login_expire', date('Y-m-d', REQUEST_TIME), '>=');

            $all = $DB->query($SQL->get(dsn()), 'all');
            $all = array_filter($all, function ($user) use ($inputPassword, $recoveryCode) {
                return acmsUserPasswordVerify($inputPassword, $user['user_pass'], getPasswordGeneration($user));
            });
        }

        //--------
        // double
        if (empty($all) or 1 < count($all)) {
            $Login->setValidator('pass', 'auth', false);
            Common::logLockPost($lockKey);
            return $this->Post;
        }

        $all = array_filter($all, function ($user) use ($inputPassword, $recoveryCode) {
            return acmsUserPasswordVerify($recoveryCode, $user['user_tfa_recovery'], 3);
        });

        //--------
        // double
        if (empty($all) or 1 < count($all)) {
            $Login->setValidator('recovery', 'auth', false);
            Common::logLockPost($lockKey);
            return $this->Post;
        }

        $row = $all[0];
        $uid = intval($row['user_id']);

        if ($uid > 0) {
            $sql = SQL::newUpdate('user');
            $sql->addUpdate('user_tfa_secret', null);
            $sql->addUpdate('user_tfa_secret_iv', null);
            $sql->addUpdate('user_tfa_recovery', null);
            $sql->addWhereOpr('user_id', $uid);
            DB::query($sql->get(dsn()), 'exec');
            ACMS_RAM::user($uid, null);

            $Login->set('tfaRecovery', 'on');
        }

        return $this->Post;
    }
}
