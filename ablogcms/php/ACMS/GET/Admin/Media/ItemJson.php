<?php

use Acms\Services\Facades\Media;

class ACMS_GET_Admin_Media_ItemJson extends ACMS_GET
{
    public function get()
    {
        try {
            if (!Media::validate()) {
                throw new \RuntimeException('You are not authorized to upload media.');
            }
            $mid = $this->Get->get('_mid');
            $json = $this->buildJson($mid);
            $json['status'] = 'success';
            Common::responseJson($json);

        } catch (\Exception $e) {
            Common::responseJson(array(
                'status' => 'failure',
                'message' => $e->getMessage(),
            ));
        }
        die();
    }


    protected function buildJson($mid)
    {
        $data = Media::getMedia($mid);
        $tags = Media::getMediaLabel($mid);

        return array(
            'item' => Media::buildJson($mid, $data, $tags, $data['bid']),
        );
    }
}
