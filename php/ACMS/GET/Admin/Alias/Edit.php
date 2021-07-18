<?php

class ACMS_GET_Admin_Alias_Edit extends ACMS_GET_Admin_Edit
{
    function edit(& $Tpl)
    {
        $Alias  =& $this->Post->getChild('alias');

        if ( $Alias->isNull() ) {
            if ( $aid = intval($this->Get->get('aid')) ) {
                $Alias->overload(loadAlias($aid));
            } else {
                $Alias->set('status', 'open');
                $Alias->set('indexing', 'on');
            }
        }

        $Blog   =& $this->Post->getChild('blog');
        $Field  =& $this->Post->getChild('field');

        if ( $Blog->isNull() ) {
            if ( 'insert' <> $this->edit ) {
                $blogInfo = loadBlog(BID);
                $blogInfo->deleteField('indexing');
                $Blog->overload($blogInfo);
                $Field->overload(loadBlogField(BID));
            } else {
                //---------
                // default
                $Blog->set('domain', DOMAIN);
                $Blog->set('status', 'open');
                $Blog->set('indexing', 'on');
            }
        }

        //---------------------------------------
        // root blog can not change status close
        if ( !!is_string($this->step) and RBID <> BID and 'close' <> $Blog->get('status') ) {
            $Tpl->add('status#close');
        }

        return true;
    }
}
