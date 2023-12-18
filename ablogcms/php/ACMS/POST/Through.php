<?php

class ACMS_POST_Through extends ACMS_POST
{
    function post()
    {
        if ($takeover = $this->Post->get('throughPost')) {
            $Post = acmsUnserialize($takeover);
            if ( method_exists($Post, 'deleteField') && method_exists($Post, 'overload') ) {
                $this->Post->deleteField('throughPost');
                $Post->overload($this->Post, true);
                $this->Post = $Post;
                $this->Post->deleteField('ajaxUploadImageAccess');
            }
        }
        return $this->Post;
    }
}
