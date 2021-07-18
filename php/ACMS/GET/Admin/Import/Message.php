<?php

class ACMS_GET_Admin_Import_Message extends ACMS_GET
{
    function get()
    {
        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        
        $importMess  = $this->Post->get('importMessage');
        $successFlag = $this->Post->get('success');

        if ( $successFlag === 'on' ) {
            $Tpl->add('import:data', array(
                'blog'          => $this->Post->get('blogName'),
                'category'      => $this->Post->get('categoryName'),
                'entry_count'   => $this->Post->get('entryCount'),
            ));
        }
        
        if ( !empty($importMess) ) {
            $Tpl->add(null, array(
                'importMessage' => $importMess,
                'success'       => $successFlag,
                'notice_mess'   => 'show',
            ));
        }
        
        return $Tpl->get();
    }
}
