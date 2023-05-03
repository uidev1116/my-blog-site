<?php

class ACMS_POST_Category_Index_Parent extends ACMS_POST_Category
{
    function post()
    {
        if ( !sessionWithCompilation() ) die();
        $toPid = idval($this->Post->get('parent'));
        if ( !empty($_POST['checks']) and is_array($_POST['checks']) ) {
            foreach ( $_POST['checks'] as $cid ) {

                if ( 1
                    and !empty($toPid) 
                    and ACMS_RAM::categoryScope($toPid) <> ACMS_RAM::categoryScope($cid)
                ) {
                    continue;
                }

                // implment ACMS_POST_Category
                $this->changeParentCategory($cid, $toPid);
            }
        }

        return $this->Post;
    }
}
