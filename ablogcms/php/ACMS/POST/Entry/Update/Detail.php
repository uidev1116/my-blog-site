<?php

class ACMS_POST_Entry_Update_Detail extends ACMS_POST_Entry_Update
{
    /**
     * ユニットを保存せずプライマリーイメージを取得
     *
     * @param array $units
     * @param int $eid
     * @param int $primary_image
     */
    protected function saveUnit($units, $eid, $primary_image)
    {
        $sql = SQL::newSelect('entry');
        $sql->addSelect('entry_primary_image');
        $sql->addWhereOpr('entry_id', $eid);
        $primaryImageId = DB::query($sql->get(dsn()), 'one');

        return $primaryImageId;
    }

    function post()
    {
        $this->lockService = App::make('entry.lock');
        $updatedResponse = $this->update();

        if (is_array($updatedResponse)) {
            $Session =& Field::singleton('session');
            $Session->add('entry_action', 'update');
            $info = [
                'bid'   => BID,
                'cid'   => $updatedResponse['cid'],
                'eid'   => EID,
            ];
            if ($updatedResponse['trash'] == 'trash') {
                $info['query'] = ['trash' => 'show'];
            }
            $this->redirect(acmsLink($info));
        }
        $this->redirect(acmsLink([
            'bid' => BID,
            'eid' => EID,
            'admin' => 'entry-edit',
        ]));
    }
}
