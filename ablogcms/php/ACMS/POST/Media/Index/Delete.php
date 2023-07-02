<?php

class ACMS_POST_Media_Index_Delete extends ACMS_POST
{
    function post()
    {

        try {
            $this->Post->reset(true);
            $this->Post->setMethod('checks', 'required');
            if (!Media::validate()) {
                $this->Post->setMethod('media', 'operable', false);
            }
            $this->Post->validate(new ACMS_Validator());
            if (!$this->Post->isValidAll()) {
                throw new \RuntimeException('Permission denied');
            }
            @set_time_limit(0);
            foreach ($this->Post->getArray('checks') as $mid) {
                $id = preg_split('@:@', $mid, 2, PREG_SPLIT_NO_EMPTY);
                $mbid = intval($id[0]);
                $mid = intval($id[1]);
                if (!(1
                    && $mid && $mbid
                    && ACMS_RAM::blogLeft(SBID) <= ACMS_RAM::blogLeft($mbid)
                    && ACMS_RAM::blogRight(SBID) >= ACMS_RAM::blogRight($mbid)
                )) {
                    continue;
                }
                Media::deleteItem($mid);
            }
            Common::responseJson(array(
                'status' => 'success'
            ));
        } catch (\Exception $e) {
            Common::responseJson(array(
                'status' => 'failure',
                'message' => $e->getMessage(),
            ));
        }
    }
}
