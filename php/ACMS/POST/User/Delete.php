<?php

class ACMS_POST_User_Delete extends ACMS_POST_User
{
    function post()
    {
        $DB     = DB::singleton(dsn());

        $User = $this->extract('user');
        $User->reset();
        
        $this->Post->reset(true);
        $this->Post->setMethod('user', 'operable', !!UID and (sessionWithAdministration() and UID <> SUID));

        //-------------
        // entryExists
        $SQL    = SQL::newSelect('entry');
        $SQL->addWhereOpr('entry_user_id', UID);
        $SQL->setLimit(1);
        $this->Post->setMethod('user', 'entryExists', !$DB->query($SQL->get(dsn()), 'one'));
        $this->Post->validate();

        if ( $this->Post->isValidAll() ) {
            //-------------
            // delete user
            $SQL    = SQL::newDelete('user');
            $SQL->addWhereOpr('user_id', UID);
            $SQL->addWhereOpr('user_blog_id', BID);
            $DB->query($SQL->get(dsn()), 'exec');
            ACMS_RAM::user(UID, null);
            
            // $str_path = $User->get('icon@squarePath');
            // if( Storage::isFile( ARCHIVES_DIR . $str_path ) ){
            //     Storage::remove( ARCHIVES_DIR . $str_path );
            // }
            // if ( HOOK_ENABLE ) {
            //     $Hook = ACMS_Hook::singleton();
            //     $Hook->call('mediaDelete', ARCHIVES_DIR.$str_path);
            // }

            //--------------
            // delete field
            Common::saveField('uid', UID);

            //-----------------
            // delete fulltext
            $SQL    = SQL::newDelete('fulltext');
            $SQL->addWhereOpr('fulltext_uid', UID);
            $DB->query($SQL->get(dsn()), 'exec');

            $this->Post->set('edit', 'delete');
        }

        return $this->Post;
    }
}
