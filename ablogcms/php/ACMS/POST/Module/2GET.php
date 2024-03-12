<?php

class ACMS_POST_Module_2GET extends ACMS_POST_Module
{
    function post()
    {
        if (sessionWithAdministration() and ($mid = idval($this->Post->get('mid')))) {
            $DB     = DB::singleton(dsn());
            $SQL    = SQL::newSelect('module');
            $SQL->setSelect('module_name');
            $SQL->addWhereOpr('module_id', $mid);

            if ($name = $DB->query($SQL->get(dsn()), 'one')) {
                $url    = acmsLink(array(
                    'bid'   => BID,
                    'admin' => 'config_' . strtolower(preg_replace('@(?<=[a-zA-Z0-9])([A-Z])@', '-$1', $name)),
                    'query' => array(
                        'mid'   => $mid,
                    ),
                ));
                $this->redirect($url);
            }
        }

        return $this->Post;
    }
}
