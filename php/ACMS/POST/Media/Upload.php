<?php

use Acms\Services\Facades\Common;
use Acms\Services\Facades\Media;

class ACMS_POST_Media_Upload extends ACMS_POST
{
    function post()
    {
        try {
            if (!Media::validate()) {
                throw new \RuntimeException('You are not authorized to upload media.');
            }
            $tags = $this->Post->get('tags');
            $name = $this->Post->get('name');
            if ($_FILES['file']['error'] != 0) {
                throw new \RuntimeException('Uploaded files are invalid.');
            }
            $info = Media::getBaseInfo($_FILES['file'], $tags, $name);
            if ($info === false) {
                throw new \RuntimeException('Uploaded files are invalid.');
            }
            if (Media::isImageFile($info['type'])) {
                $data = Media::uploadImage('file');
            } else if (Media::isSvgFile($info['type'])) {
                $data = Media::uploadSvg($info['size'], 'file');
            } else {
                $data = Media::uploadFile($info['size'], 'file');
            }
            if (empty($data)) {
                throw new \RuntimeException('Upload failed.');
            }
            $mid = DB::query(SQL::nextval('media_id', dsn()), 'seq');

            if (isset($_FILES['media_pdf_thumbnail'])) {
                $res = Media::uploadPdfThumbnail('media_pdf_thumbnail');
                if (isset($res['path'])) {
                    $data['thumbnail'] = $res['path'];
                    $data['field_6'] = 1;
                }
            }
            Media::insertMedia($mid, $data, BID);
            Media::saveTags($mid, $tags, BID);

            $data = Media::getMedia($mid);
            $tags = Media::getMediaLabel($mid);
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
