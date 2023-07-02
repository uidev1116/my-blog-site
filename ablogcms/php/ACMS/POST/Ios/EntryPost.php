<?php

class ACMS_POST_Ios_EntryPost extends ACMS_POST_Entry_Insert
{
    /**
     * @see ACMS_POST_Entry_Insert::insert()
     */
    function post()
    {
        $insertedResponse = $this->insert();
        
        //-------
        // cache
        if ( $this->isCacheDelete ) {
            ACMS_POST_Cache::clearPageCache(BID);
        }
        
        die(strval($insertedResponse['eid']).':s:'.strval($insertedResponse['ecd']));
    }
}