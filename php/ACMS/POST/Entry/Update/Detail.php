<?php

class ACMS_POST_Entry_Update_Detail extends ACMS_POST_Entry_Update
{
    function saveColumn(& $Column, $eid, $bid, $add=false, $rvid=null, $moveArchive=false)
    {
        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('entry');
        $SQL->addSelect('entry_primary_image');
        $SQL->addWhereOpr('entry_id', $eid);
        $SQL->addWhereOpr('entry_blog_id', $bid);
        $Res = array($DB->query($SQL->get(dsn()), 'one'));
        return $Res;
    }

    function post()
    {
        $updatedResponse = $this->update();

        if ( is_array($updatedResponse) ) {
            $Session =& Field::singleton('session');
            $Session->add('entry_action', 'update');

            $info   = array(
                'bid'   => BID,
                'cid'   => $updatedResponse['cid'],
                'eid'   => EID,
            );
            if ( $updatedResponse['trash'] == 'trash' ) {
                $info['query'] = array('trash' => 'show');
            }
            $this->redirect(acmsLink($info));
        }
        $this->redirect(acmsLink(array(
                'bid'   => BID,
                'eid'   => EID,
                'admin' => 'entry-edit',
        )));
        return $this->Post;
    }
}
