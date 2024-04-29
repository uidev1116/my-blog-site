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
            AcmsLogger::notice('メディアの詳細情報のJSON取得に失敗しました', Common::exceptionArray($e));

            Common::responseJson([
                'status' => 'failure',
                'message' => $e->getMessage(),
            ]);
        }
    }


    protected function buildJson($mid)
    {
        $data = Media::getMedia($mid);
        $tags = Media::getMediaLabel($mid);

        return [
            'item' => Media::buildJson($mid, $data, $tags, $data['bid']),
        ];
    }
}
