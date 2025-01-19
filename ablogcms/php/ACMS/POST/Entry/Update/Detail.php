<?php

use Acms\Services\Facades\Application;

class ACMS_POST_Entry_Update_Detail extends ACMS_POST_Entry_Update
{
    /**
     * ユニットを保存せずプライマリーイメージを取得
     *
     * @param \Acms\Services\Unit\Contracts\Model[] $units
     * @param int $eid
     * @param string|null $primary_image
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
        $this->unitRepository = Application::make('unit-repository');
        $this->lockService = Application::make('entry.lock');
        assert($this->unitRepository instanceof \Acms\Services\Unit\Repository);
        assert($this->lockService instanceof \Acms\Services\Entry\Lock);

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
