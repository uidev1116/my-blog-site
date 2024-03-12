<?php

class ACMS_GET_Admin_Form_Log extends ACMS_GET_Admin_Module
{
    function get()
    {
        if ('form_log' <> ADMIN) {
            return '';
        }
        if (!($fmid = intval($this->Get->get('fmid')))) {
            return false;
        }
        if (
            0
            || ( !roleAvailableUser() && !sessionWithFormAdministration() )
            || ( roleAvailableUser() && !roleAuthorization('form_view', BID) && !roleAuthorization('form_edit', BID) )
        ) {
            return '';
        }

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $Vars   = array();

        //---------
        // refresh
        if (!$this->Post->isNull()) {
            $Vars['notice_mess'] = 'show';
            $Tpl->add('refresh');
        }

        //---------------
        // To or adminTo
        $to = $this->Get->get('mailTo', 'to');
        $Vars['mailTo:checked#' . $to]    = config('attr_checked');

        //-------
        // order
        $order  = ORDER ? ORDER : 'datetime-desc';
        $Vars['order:selected#' . $order] = config('attr_selected');

        //--------
        // limit
        $limits = configArray('admin_limit_option');
        $limit  = $this->Q->get('limit', $limits[config('admin_limit_default')]);
        foreach ($limits as $val) {
            $_vars  = array('value' => $val);
            if ($limit == $val) {
                $_vars['selected'] = config('attr_selected');
            }
            $Tpl->add('limit:loop', $_vars);
        }

        //-------
        // span
        $Vars   += array(
            'start' => START,
            'end'   => END,
        );

        $DB     = DB::singleton(dsn());

        $SQL    = SQL::newSelect('form');
        $SQL->addSelect('form_name');
        $SQL->addSelect('form_blog_id');
        $SQL->addWhereOpr('form_id', $fmid);
        $SQL->addLeftJoin('blog', 'blog_id', 'form_blog_id');
        ACMS_Filter::blogTree($SQL, BID, 'ancestor-or-self');

        $Where  = SQL::newWhere();
        $Where->addWhereOpr('form_blog_id', BID, '=', 'OR');
        $Where->addWhereOpr('form_scope', 'global', '=', 'OR');
        $SQL->addWhere($Where);

        $form = $DB->query($SQL->get(dsn()), 'row');

        if (empty($form)) {
            return false;
        }

        $SQL    = SQL::newSelect('log_form');
        $SQL->addWhereOpr('log_form_form_id', $fmid);
        $SQL->addLeftJoin('blog', 'blog_id', 'log_form_blog_id');
        ACMS_Filter::blogTree($SQL, BID, 'ancestor-or-self');
        $SQL->addWhereBw('log_form_datetime', START, END);

        if ($this->Get->isExists('serial')) {
            $SQL->addWhereOpr('log_form_serial', $this->Get->get('serial'));
        }

        $Amount = new SQL_Select($SQL);
        $Amount->setSelect('*', 'form_amount', null, 'count');

        if (!$pageAmount = $DB->query($Amount->get(dsn()), 'one')) {
            $Vars['notice_mess'] = 'show';
            $Tpl->add('index#notFound');
            $Tpl->add(null, $Vars);
            return $Tpl->get();
        }
        $Vars   += $this->buildPager(
            PAGE,
            $limit,
            $pageAmount,
            config('admin_pager_delta'),
            config('admin_pager_cur_attr'),
            $Tpl,
            array(),
            array('admin' => ADMIN)
        );

        list($fd, $sort) = explode('-', $order);
        $SQL->setOrder('log_form_' . $fd, strtoupper($sort));
        $SQL->setLimit($limit, (PAGE - 1) * $limit);

        $q  = $SQL->get(dsn());
        $DB->query($q, 'fetch');
        while ($row = $DB->fetch($q)) {
            if (isset($row['log_form_version']) && intval($row['log_form_version']) === 1) {
                $log_subject        = acmsUnserialize($row['log_form_mail_subject']);
                $log_body           = acmsUnserialize($row['log_form_mail_body']);
                $log_admin_subject  = acmsUnserialize($row['log_form_mail_subject_admin']);
                $log_admin_body     = acmsUnserialize($row['log_form_mail_body_admin']);
            } else {
                $log_subject        = $row['log_form_mail_subject'];
                $log_body           = $row['log_form_mail_body'];
                $log_admin_subject  = $row['log_form_mail_subject_admin'];
                $log_admin_body     = $row['log_form_mail_body_admin'];
            }

            $log = array(
                'bid'               => $row['log_form_blog_id'],
                'fmid'              => $fmid,
                'serial'            => $row['log_form_serial'],
                'datetime'          => $row['log_form_datetime'],
                'mail_to'           => $row['log_form_mail_to'],
            );

            if (isset($row['log_form_version']) && intval($row['log_form_version']) === 1) {
                $Field   = acmsUnserialize($row['log_form_data']);
            } else {
                $Field = Common::safeUnSerialize($row['log_form_data']);
            }
            if (method_exists($Field, 'isNull') && !$Field->isNull()) {
                $log += $this->buildField($Field, $Tpl, 'log:loop');
            }

            if ($to === 'adminTo') {
                $log['mail_subject']    = $log_admin_subject;
                $log['mail_body']       = $log_admin_body;
            } else {
                $log['mail_subject']    = $log_subject;
                $log['mail_body']       = $log_body;
            }
            $Tpl->add('log:loop', $log);
        }

        $Vars['formName']   = $form['form_name'];
        $originalBid        = $form['form_blog_id'];

        if (
            $originalBid == BID && ( 0
            || ( !roleAvailableUser() && sessionWithAdministration($originalBid) )
            || ( roleAvailableUser() && roleAuthorization('form_edit', $originalBid) )
            )
        ) {
            $Tpl->add('deleteAction');
        }

        $Tpl->add(null, $Vars);
        return $Tpl->get();
    }
}
