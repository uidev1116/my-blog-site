<?php

use Acms\Services\Facades\Common;
use Acms\Services\Facades\Media;

class ACMS_POST_Media_UpdateAsNew extends ACMS_POST_Media_Update
{
    public function post()
    {
        $mid = $this->Get->get('_mid', false);
        $data = [];

        try {
            if (!Media::validate()) {
                throw new \RuntimeException('メディア機能が有効でないか、権限がありません');
            }
            $Media = $this->extract('media');
            if (empty($mid)) {
                $Media->setMethod('media', 'operable', false);
            }
            $Media->validate(new ACMS_Validator_Media());
            if (!$this->Post->isValidAll()) {
                throw new \RuntimeException('メディアが指定されていない、または権限がありません');
            }
            $tags = $Media->get('media_label');
            $oldData = Media::getMedia($mid);

            if (isset($_FILES[$this->uploadFieldName])) {
                $name = $Media->get('file_name');
                Common::validateFileUpload($this->uploadFieldName);

                $info = Media::getBaseInfo($_FILES[$this->uploadFieldName], $tags, $name);
                $replaced = $Media->get('replaced') === 'true';
                $type = mime_content_type($_FILES[$this->uploadFieldName]['tmp_name']);

                if (Media::isImageFile($type)) {
                    $_FILES[$this->uploadFieldName]['name'] = $name;
                    $data = Media::uploadImage($this->uploadFieldName, $replaced);
                    if ($replaced) {
                        $data['original'] = otherSizeImagePath($data['path'], 'large');
                    }
                } elseif (Media::isSvgFile($type)) {
                    $data = Media::uploadSvg($info['size'], $this->uploadFieldName);
                } else {
                    $data = Media::uploadFile($info['size'], $this->uploadFieldName);
                }
                $data['upload_date'] = $oldData['upload_date'];
            } else {
                if (in_array($oldData['type'], array('file', 'svg'))) {
                    $data = array_merge($oldData, Media::copyFiles($mid));
                } else {
                    $data = array_merge($oldData, Media::copyImages($mid));
                }
            }
            // pdf thumbnail
            if (isset($_FILES['media_pdf_thumbnail'])) {
                $res = Media::uploadPdfThumbnail('media_pdf_thumbnail');
                if (isset($res['path'])) {
                    $data['thumbnail'] = $res['path'];
                }
            }
            $data['update_date'] = date('Y-m-d H:i:s', REQUEST_TIME);
            $data['status'] = $Media->get('status');
            $data['field_1'] = $Media->get('field_1');
            $data['field_2'] = $Media->get('field_2');
            $data['field_3'] = $Media->get('field_3');
            $data['field_4'] = $Media->get('field_4');
            if ($rename = $Media->get('rename')) {
                $data = Media::rename($data, $rename);
            }
            if ($Media->get('focal_x') && $Media->get('focal_y')) {
                $data['field_5'] = $Media->get('focal_x') . ',' . $Media->get('focal_y');
            } else {
                $data['field_5'] = '';
            }
            if ($pdfPage = $Media->get('pdf_page')) {
                $data['field_6'] = $pdfPage;
            } else {
                $data['field_6'] = '';
            }
            $mid = DB::query(SQL::nextval('media_id', dsn()), 'seq');
            Media::insertMedia($mid, $data, BID);
            Media::saveTags($mid, $tags, BID);

            $data = Media::getMedia($mid);
            $tags = Media::getMediaLabel($mid);
            $json = Media::buildJson($mid, $data, $tags, BID);
            $json['status'] = 'success';
            Common::responseJson($json);
        } catch (\Exception $e) {
            AcmsLogger::info('新しいメディアとして作成に失敗しました。' . $e->getMessage(), Common::exceptionArray($e, ['mid' => $mid, 'data' => $data]));

            Common::responseJson(array(
                'status' => 'failure',
                'message' => $e->getMessage(),
            ));
        }
        die();
    }
}
