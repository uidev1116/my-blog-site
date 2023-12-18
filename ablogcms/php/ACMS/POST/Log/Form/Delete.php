<?php

class ACMS_POST_Log_Form_Delete extends ACMS_POST
{
    function delete($fmid, $sid, $to)
    {
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newDelete('log_form');
        $SQL->addWhereOpr('log_form_form_id', $fmid);
        $SQL->addWhereOpr('log_form_serial', $sid);
        if ( !empty($to) ) {
            $SQL->addWhereOpr('log_form_mail_to', $to);
        }
        $SQL->addWhereOpr('log_form_blog_id', BID);
        $DB->query($SQL->get(dsn()), 'exec');
    }

    function post()
    {
        $this->Post->setMethod('log', 'fmidIsNull', !!($fmid = intval($this->Get->get('fmid'))));
        $this->Post->setMethod('log', 'operative', sessionWithAdministration());
        if ( roleAvailableUser() ) {
            $this->Post->setMethod('log', 'operative', roleAuthorization('form_edit', BID));
        } else {
            $this->Post->setMethod('log', 'operative', sessionWithAdministration());
        }

        $this->Post->validate();

        if ( $this->Post->isValidAll() ) {
            $DB     = DB::singleton(dsn());
            $SQL    = SQL::newDelete('log_form');
            $SQL->addWhereOpr('log_form_form_id', $fmid);
            $SQL->addWhereOpr('log_form_blog_id', BID);
            $SQL->addWhereBw('log_form_datetime', START, END);
            $DB->query($SQL->get(dsn()), 'exec');
            $this->redirect(acmsLink(array(
                'bid'   => BID,
                'admin' => 'form_log',
                'query' => array(
                    'fmid'  => $fmid,
                ),
            )));
        }

        return $this->Post;
    }
}
