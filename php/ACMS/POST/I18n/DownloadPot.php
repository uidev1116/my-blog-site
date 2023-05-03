<?php

class ACMS_POST_I18n_DownloadPot extends ACMS_POST
{
    function post()
    {
        if ( !sessionWithAdministration() ) die();

        try {
            $pot='lang/messages.pot';
            if ( xi18n(null, $pot) ) {
                // download
                header('Content-Type: application/force-download');
                header('Content-Length: '.filesize($pot));
                header('Content-disposition: attachment; filename="messages.pot"');
                readfile($pot);
            }
        } catch ( \Exception $e ) {
            $this->addError($e->getMessage());
        }
        return $this->Post;
    }
}
