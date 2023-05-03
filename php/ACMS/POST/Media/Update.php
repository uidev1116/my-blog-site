<?php

use Acms\Services\Facades\Common;
use Acms\Services\Facades\Media;
use Acms\Services\Facades\Storage;

class ACMS_POST_Media_Update extends ACMS_POST_Media
{
    protected $uploadFieldName = 'media_file';

    public function post()
    {
        try {
            if (!Media::validate()) {
                throw new \RuntimeException('You are not authorized to upload media.');
            }
            $mid = $this->Get->get('_mid', false);
            $Media = $this->extract('media');
            if (empty($mid) || !Media::canEdit($mid)) {
                $Media->setMethod('media', 'operable', false);
            }
            $Media->validate(new ACMS_Validator_Media());
            if (!$this->Post->isValidAll()) {
                throw new \RuntimeException('You are not authorized to upload media.');
            }

            $tags = $Media->get('media_label');
            $oldData = Media::getMedia($mid);

            if (isset($_FILES[$this->uploadFieldName])) {
                $name = $Media->get('file_name');
                $old = $Media->get('media_old');
                $info = Media::getBaseInfo($_FILES[$this->uploadFieldName], $tags, $name);
                $replaced = $Media->get('replaced') === 'true';
                if (Media::isImageFile($info['type'])) {
                    $_FILES[$this->uploadFieldName]['name'] = $name;
                    $data = Media::uploadImage($this->uploadFieldName, $replaced);
                    if ($replaced) {
                        $data['original'] = otherSizeImagePath($data['path'], 'large');
                    }
                    Media::deleteImageFile($old, $replaced);
                } else if (Media::isSvgFile($info['type'])) {
                    $data = Media::uploadSvg($info['size'], $this->uploadFieldName);
                    Media::deleteFile($mid);
                } else {
                    $data = Media::uploadFile($info['size'], $this->uploadFieldName);
                    Media::deleteFile($mid);
                }
                $data['upload_date'] = $oldData['upload_date'];
            } else {
                $data = $oldData;
            }
            // pdf thumbnail
            if (isset($_FILES['media_pdf_thumbnail'])) {
                Media::deleteImageFile($data['thumbnail'], true);
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
            Media::updateMedia($mid, $data, BID);
            Media::saveTags($mid, $tags, BID);

            $data = Media::getMedia($mid);
            $tags = Media::getMediaLabel($mid);
            $data['editable'] = true;
            $json = Media::buildJson($mid, $data, $tags, BID);
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
}
