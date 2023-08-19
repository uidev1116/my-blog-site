<?php

namespace Acms\Services\Media;

use DB;
use SQL;
use SQL_Select;
use ACMS_RAM;
use ACMS_Hook;
use Acms\Services\Facades\Image;
use Acms\Services\Facades\Storage;
use Acms\Services\Facades\Common;

class Helper
{
    public function validate($bid = BID)
    {
        if (!IS_LICENSED) {
            return false;
        }
        if (config('media_library') !== 'on') {
            return false;
        }
        if (roleAvailableUser()) {
            if (!roleAuthorization('media_upload', $bid)) {
                return false;
            }
        } else {
            if (!sessionWithContribution($bid, false)) {
                return false;
            }
        }
        return true;
    }

    public function canEdit($mid)
    {
        if (sessionWithCompilation()) {
            return true;
        }
        if (isSessionContributor()) {
            $sql = SQL::newSelect('media');
            $sql->setSelect('media_user_id');
            $sql->addWhereOpr('media_id', $mid);
            $ownerId = DB::query($sql->get(dsn()), 'one');
            if (intval(SUID) === intval($ownerId)) {
                return true;
            }
        }
        return false;
    }

    public function getBaseInfo($fileObj, $tags, $name)
    {
        if (!preg_match('@\.([^.]+)$@', $fileObj['name'], $match) && $fileObj['name'] !== 'blob') {
            return false;
        }
        $info = getimagesize($fileObj['tmp_name']);
        $extension = Image::detectImageExtenstion($info['mime']);

        return array(
            'tags' => $tags,
            'name' => $name,
            'file' => $fileObj,
            'size' => $fileObj['size'],
            'type' => $fileObj['type'],
            'extension' => $extension,
        );
    }

    public function copyImages($mid)
    {
        $oldData = $this->getMedia($mid);
        $oldPath = $oldData['path'];

        $oldPath = MEDIA_LIBRARY_DIR . $oldPath;
        $info = pathinfo($oldPath);
        $name = preg_replace('/\.[^.]*$/u', '', Storage::mbBasename($oldData['name']));
        $name = preg_replace('/\s/u', '_', $name);
        $dir = empty($info['dirname']) ? '' : $info['dirname'] . '/';
        $ext = empty($info['extension']) ? '' : '.' . $info['extension'];

        $newPath = $dir . $name . $ext;
        $i = 1;
        while (Storage::isReadable($newPath)) {
            if (preg_match('/\_[\d]{6}\_[\d]{6}$/', $name)) {
                $name = $name . '_' . $i;
            } else if (preg_match('/\_[\d]{6}\_[\d]{6}\_[\d]+$/', $name)) {
                $name = preg_replace('/\_[\d]+$/', '_' . $i, $name);
            } else {
                $name = $name . date('_ymd_His', REQUEST_TIME);
            }
            $newPath = $dir . $name . $ext;
            $i++;
        }
        copyFile($oldPath, $newPath);
        copyFile(otherSizeImagePath($oldPath, 'large'), otherSizeImagePath($newPath, 'large'));
        copyFile(otherSizeImagePath($oldPath, 'tiny'), otherSizeImagePath($newPath, 'tiny'));

        return array(
            'path' => substr($newPath, strlen(MEDIA_LIBRARY_DIR)),
            'name' => $name . $ext,
        );
    }

    public function copyFiles($mid)
    {
        $oldData = $this->getMedia($mid);
        $oldPath = $oldData['path'];
        $status = $oldData['status'];
        $baseDir = $status ? MEDIA_STORAGE_DIR : MEDIA_LIBRARY_DIR;

        $oldPath = $baseDir . $oldPath;
        $info = pathinfo($oldPath);
        $name = preg_replace('/\.[^.]*$/u', '', Storage::mbBasename($oldPath));
        $name = preg_replace('/\s/u', '_', $name);
        $dir = empty($info['dirname']) ? '' : $info['dirname'] . '/';
        $ext = empty($info['extension']) ? '' : $info['extension'];

        $newPath = $dir . $name . '.' . $ext;
        $i = 1;
        while (Storage::isReadable($newPath)) {
            if (preg_match('/\_[\d]{6}\_[\d]{6}$/', $name)) {
                $name = $name . '_' . $i;
            } else if (preg_match('/\_[\d]{6}\_[\d]{6}\_[\d]+$/', $name)) {
                $name = preg_replace('/\_[\d]+$/', '_' . $i, $name);
            } else {
                $name = $name . date('_ymd_His', REQUEST_TIME);
            }
            $newPath = $dir . $name . '.' . $ext;
            $i++;
        }
        copyFile($oldPath, $newPath);

        return array(
            'path' => substr($newPath, strlen($baseDir)),
            'name' => $name . '.' . $ext,
        );
    }

    public function uploadImage($fieldName = 'file', $original = true)
    {
        $size = array(
            'normal' => 0,
            'tiny' => 330,
        );
        if ($original) {
            $size['large'] = 99999;
        }
        $isRamdomFileName = config('media_image_ramdom_filename', 'off') === 'on';
        $data = createImages($size, MEDIA_LIBRARY_DIR, $fieldName, $isRamdomFileName, null, true);
        $data['extension'] = $data['type'];
        $data['type'] = 'image';
        $data['name'] = Storage::mbBasename($data['path']);
        $data['filesize'] = @filesize(MEDIA_LIBRARY_DIR . $data['path']);

        return $data;
    }

    public function uploadPdfThumbnail($name)
    {
        return createImages(array('normal' => 99999), MEDIA_LIBRARY_DIR, $name, true, null, true);
    }

    public function uploadSvg($size, $fieldName = 'file')
    {
        $data = createFile(MEDIA_LIBRARY_DIR, $fieldName, false);
        $data['extension'] = $data['type'];
        $data['type'] = 'svg';
        $data['size'] = '';
        $data['filesize'] = $size;

        return $data;
    }

    public function uploadFile($size, $fieldName = 'file')
    {
        Storage::makeDirectory(MEDIA_STORAGE_DIR);

        $data = createFile(MEDIA_STORAGE_DIR, $fieldName, false);
        if (!Storage::exists(MEDIA_STORAGE_DIR . $data['path'])) {
            return false;
        }
        $data['extension'] = $data['type'];
        $data['type'] = 'file';
        $data['size'] = '';
        $data['filesize'] = $size;

        return $data;
    }

    public function deleteImageFile($path, $removeOriginal = true)
    {
        $edited = MEDIA_LIBRARY_DIR . $path;
        $original = MEDIA_LIBRARY_DIR . otherSizeImagePath($path, 'large');
        Storage::remove($edited);
        if (Storage::exists($edited . '.webp')) {
            Storage::remove($edited . '.webp');
        }
        if ($dirname = dirname($edited)) {
            $dirname .= '/';
        }
        $basename = Storage::mbBasename($edited);
        $images = glob($dirname . '*-' . $basename);
        if (is_array($images)) {
            foreach ($images as $filename) {
                if (!$removeOriginal && $filename === $original) {
                    continue;
                }
                Storage::remove($filename);
                if (Storage::exists($filename . '.webp')) {
                    Storage::remove($filename . '.webp');
                }
                if (HOOK_ENABLE) {
                    $Hook = ACMS_Hook::singleton();
                    $Hook->call('mediaDelete', $filename);
                }
            }
        }
    }

    public function deleteFile($mid)
    {
        $oldData = $this->getMedia($mid);
        $status = $oldData['status'];
        $path = $oldData['path'];
        $baseDir = $status ? MEDIA_STORAGE_DIR : MEDIA_LIBRARY_DIR;
        Storage::remove($baseDir . $path);
        if (HOOK_ENABLE) {
            $Hook = ACMS_Hook::singleton();
            $Hook->call('mediaDelete', $baseDir . $path);
        }
    }

    public function urlencode($path)
    {
        $name = Storage::mbBasename($path);
        return substr($path, 0, strlen($path) - strlen($name)) . urlencode($name);
    }

    public function isImageFile($type)
    {
        return preg_match('/^image/', $type) && !preg_match('/svg/', $type);
    }

    public function isSvgFile($type)
    {
        return preg_match('/svg/', $type);
    }

    public function isFile($type)
    {
        return preg_match('/^file/', $type);
    }

    public function getEdited($path)
    {
        return '/' . DIR_OFFSET . MEDIA_LIBRARY_DIR . $path;
    }

    public function getImageThumbnail($path)
    {
        return '/' . DIR_OFFSET . MEDIA_LIBRARY_DIR . otherSizeImagePath($path,'tiny');
    }

    public function getSvgThumbnail($path)
    {
        return '/' . DIR_OFFSET . MEDIA_LIBRARY_DIR . $path;
    }

    public function getFileThumbnail($extension)
    {
        return '/' . DIR_OFFSET . pathIcon($extension);
    }

    public function getPdfThumbnail($path)
    {
        return '/' . DIR_OFFSET . MEDIA_LIBRARY_DIR . $path;
    }

    public function getImagePermalink($path)
    {
        $permalink = BASE_URL . MEDIA_LIBRARY_DIR . $path;
        if (ARCHIVES_CACHE_SERVER) {
            $permalink = ARCHIVES_CACHE_SERVER . '/' . DIR_OFFSET . MEDIA_LIBRARY_DIR . $path;
        }
        return $permalink;
    }

    public function getFilePermalink($mid, $fullpath = true)
    {
        if ($fullpath) {
            return acmsLink(array('bid'=>BID), false) . MEDIA_FILE_SEGMENT . '/' . $mid . '/' . $this->getDownloadLinkHash($mid) . '/';
        }
        $offset = rtrim(DIR_OFFSET . acmsPath(array('bid' => BID)), '/');
        if (strlen($offset) > 1) {
            $offset .= '/';
        }
        return '/' . $offset .  MEDIA_FILE_SEGMENT . '/' . $mid . '/' . $this->getDownloadLinkHash($mid) . '/';
    }

    public function getDownloadLinkHash($mid)
    {
        $pepper = sha1('pepper' . PASSWORD_SALT_1);
        $hash = sha1($pepper . $mid . PASSWORD_SALT_1);

        return substr($hash, 0, 16);
    }

    public function getFileOldPermalink($path, $fullpath = true)
    {
        if ($fullpath) {
            return BASE_URL . MEDIA_LIBRARY_DIR . $path;
        }
        return '/' . DIR_OFFSET . MEDIA_LIBRARY_DIR . $path;
    }

    public function getOriginal($original)
    {
        if ($original && !Storage::exists(MEDIA_LIBRARY_DIR . $original)) {
            $original = '';
        }
        if (empty($original)) {
            return '';
        }
        return '/' . DIR_OFFSET . MEDIA_LIBRARY_DIR . $original;
    }

    public function filterTag($SQL, $tags)
    {
        if (!is_array($tags) and empty($tags)) return false;

        $tag = array_shift($tags);
        $SQL->addLeftJoin('media_tag', 'media_tag_media_id', 'media_id', 'tag0');
        $SQL->addWhereOpr('media_tag_name', $tag, '=', 'AND', 'tag0');
        $i  = 1;
        while ( $tag = array_shift($tags) ) {
            $SQL->addLeftJoin('media_tag', 'media_tag_media_id', 'media_tag_media_id', 'tag'.$i, 'tag'.($i-1));
            $SQL->addWhereOpr('media_tag_name', $tag, '=', 'AND', 'tag'.$i);
            $i++;
        }
    }

    public function saveTags($mid, $tags, $bid = BID)
    {
        $SQL = SQL::newDelete('media_tag');
        $SQL->addWhereOpr('media_tag_media_id', $mid);
        DB::query($SQL->get(dsn()), 'exec');

        $tags = Common::getTagsFromString($tags);
        foreach ($tags as $sort => $tag) {
            $SQL = SQL::newInsert('media_tag');
            $SQL->addInsert('media_tag_name', $tag);
            $SQL->addInsert('media_tag_sort', $sort + 1);
            $SQL->addInsert('media_tag_media_id', $mid);
            $SQL->addInsert('media_tag_blog_id', $bid);

            DB::query($SQL->get(dsn()), 'exec');
        }
    }

    public function deleteTag($tagName, $bid = BID)
    {
        $DB = DB::singleton(dsn());
        $SQL = SQL::newDelete('media_tag');
        $SQL->addWhereOpr('media_tag_name', $tagName);
        $SQL->addWhereOpr('media_tag_blog_id', $bid);
        $DB->query($SQL->get(dsn()), 'exec');
    }

    public function updateTag($oldTag, $newTag, $bid = BID)
    {
        $DB = DB::singleton(dsn());
        $SQL    = SQL::newSelect('media_tag');
        $SQL->setSelect('media_tag_media_id');
        $SQL->addWhereIn('media_tag_name', array($oldTag, $newTag));
        $SQL->addWhereOpr('media_tag_blog_id', BID);
        $SQL->setGroup('media_tag_media_id');
        $SQL->setHaving(SQL::newOpr('media_tag_media_id', 2, '>=', null, 'COUNT'));
        $q  = $SQL->get(dsn());

        if ( $DB->query($q, 'fetch') and ($row = $DB->fetch($q)) ) {
            do {
                $eid    = intval($row['media_tag_media_id']);
                $Del    = SQL::newDelete('media_tag');
                $Del->addWhereOpr('media_tag_name', $newTag);
                $Del->addWhereOpr('media_tag_media_id', $eid);
                $Del->addWhereOpr('media_tag_blog_id', BID);
                $DB->query($Del->get(dsn()), 'exec');
            } while ( $row = $DB->fetch($q) );
        }

        $SQL    = SQL::newUpdate('media_tag');
        $SQL->setUpdate('media_tag_name', $newTag);
        $SQL->addWhereOpr('media_tag_name', $oldTag);
        $SQL->addWhereOpr('media_tag_blog_id', $bid);
        $DB->query($SQL->get(dsn()), 'exec');
    }

    public function insertMedia($mid, $data)
    {
        $DB = DB::singleton(dsn());

        $SQL = SQL::newInsert('media');
        $SQL->addInsert('media_id', $mid);
        $SQL->addInsert('media_type', $data['type']);
        $SQL->addInsert('media_extension', $data['extension']);
        $SQL->addInsert('media_path', $data['path']);
        $SQL->addInsert('media_file_name', $data['name']);
        $SQL->addInsert('media_file_size', $data['filesize']);
        $SQL->addInsert('media_image_size', $data['size']);
        if (isset($data['thumbnail'])) {
            $SQL->addInsert('media_thumbnail', $data['thumbnail']);
        }
        if ($data['type'] === 'file') {
            if (isset($data['status']) && $data['status']) {
                $SQL->addInsert('media_status', $data['status']);
            } else {
                $SQL->addInsert('media_status', config('media_default_status', 'entry'));
                $data['status'];
            }
        } else {
            $SQL->addInsert('media_original', otherSizeImagePath($data['path'],'large'));
        }
        foreach (array('1', '2', '3', '4', '5', '6') as $i) {
            if (isset($data['field_' . $i])) {
                $SQL->addInsert('media_field_' . $i, $data['field_' . $i]);
            }
        }
        $SQL->addInsert('media_upload_date', date('Y-m-d H:i:s', REQUEST_TIME));
        $SQL->addInsert('media_update_date', date('Y-m-d H:i:s', REQUEST_TIME));
        $SQL->addInsert('media_user_id', SUID);
        $SQL->addInsert('media_blog_id', BID);

        $DB->query($SQL->get(dsn()), 'exec');
    }

    public function updateMedia($mid, $data)
    {
        $DB = DB::singleton(dsn());
        if (isset($data['original'])) {
            $old = loadMedia($mid);
            Storage::remove($old->get('original'));
        }
        $field = array(
            'type' => 'media_type',
            'status' => 'media_status',
            'extension' => 'media_extension',
            'path' => 'media_path',
            'original' => 'media_original',
            'name' => 'media_file_name',
            'filesize' => 'media_file_size',
            'size' => 'media_image_size',
            'update_date' => 'media_update_date',
            'thumbnail' => 'media_thumbnail',
            'field_1' => 'media_field_1',
            'field_2' => 'media_field_2',
            'field_3' => 'media_field_3',
            'field_4' => 'media_field_4',
            'field_5' => 'media_field_5',
            'field_6' => 'media_field_6',
        );
        $SQL = SQL::newUpdate('media');
        foreach ($field as $key => $column) {
            if (isset($data[$key])) {
                $SQL->addUpdate($column, $data[$key]);
            }
        }
        $SQL->addWhereOpr('media_id', $mid);
        $DB->query($SQL->get(dsn()), 'exec');
    }

    public function buildJson($mid, $data, $tags, $bid = BID)
    {
        $path = $this->urlencode($data['path']);
        $type = $data['type'];
        $extension = $data['extension'];
        $original = '';
        $edited = '';
        if ($type === 'file') {
            if (empty($data['status'])) {
                $permalink = $this->getFileOldPermalink($path);
            } else {
                $permalink = $this->getFilePermalink($mid);
            }
            if (strtolower($extension) === 'pdf' && $data['thumbnail']) {
                $thumbnail = $this->getPdfThumbnail($data['thumbnail']);
            } else {
                $thumbnail = $this->getFileThumbnail($extension);
            }
        } else {
            $edited = $this->getEdited($path);
            $permalink = $this->getImagePermalink($path);
            $original = $this->urlencode($this->getOriginal($data['original']));
            if ($type === 'svg') {
                $thumbnail = $this->getSvgThumbnail($path);
            } else {
                $thumbnail = $this->getImageThumbnail($path);
            }
        }
        return array(
            "media_status" => $data['status'],
            "media_title" => $data['name'],
            "media_label" => $tags,
            "media_last_modified" => $data['update_date'],
            "media_datetime" => $data['upload_date'],
            "media_id" => intval($mid),
            "media_bid" => intval($bid),
            "media_blog_name" => isset($data['blog_name']) ? $data['blog_name'] : ACMS_RAM::blogName($bid),
            "media_size" => $data['size'],
            "media_filesize" => intval($data['filesize']),
            "media_path" => $path,
            "media_edited" => $edited,
            "media_original" => $original,
            "media_thumbnail" => $thumbnail,
            "media_permalink" => $permalink,
            "media_type" => $type,
            "media_ext" => $extension,
            "media_caption" => isset($data['field_1']) ? $data['field_1'] : '',
            "media_link" => isset($data['field_2']) ? $data['field_2'] : '',
            "media_alt" => isset($data['field_3']) ? $data['field_3']: '',
            "media_text" => isset($data['field_4']) ? $data['field_4'] : '',
            "media_focal_point" => isset($data['field_5']) ? $data['field_5'] : '',
            "media_editable" => isset($data['editable']) ? $data['editable'] : false,
            "media_pdf_page" => isset($data['field_6']) ? $data['field_6'] : '',
            "checked" => false
        );
    }

    public function getMediaArchiveList($sql)
    {
        $archives = array();
        $archive = new SQL_Select($sql);
        $archive->addSelect('media_upload_date');
        $archive->addSelect(SQL::newFunction('media_upload_date', array('SUBSTR', 0, 7)), 'media_date');
        $archive->addGroup('media_date');
        $all = DB::query($archive->get(dsn()), 'all');
        foreach ($all as $row) {
            $archives[] = $row['media_date'];
        }
        return $archives;
    }

    public function getMediaTagList($sql)
    {
        $tags = array();
        $tag = new SQL_Select($sql);
        $tag->addLeftJoin('media_tag', 'media_tag_media_id', 'media_id');
        $tag->addGroup('media_tag_name');
        $all = DB::query($tag->get(dsn()), 'all');
        foreach ($all as $row) {
            if ($row['media_tag_name']) {
                $tags[] = $row['media_tag_name'];
            }
        }
        return $tags;
    }

    public function getMediaExtensionList($sql)
    {
      $exts = array();
      $ext = new SQL_Select($sql);
      $ext->addGroup('media_extension');
      $all = DB::query($ext->get(dsn()), 'all');
      foreach ($all as $row) {
        if ($row['media_extension']) {
            $exts[] = $row['media_extension'];
        }
      }
      return $exts;
    }

    public function getMediaLabel($mid)
    {
        $label = '';
        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('media_tag');
        $SQL->addSelect('media_tag_name');
        $SQL->addWhereOpr('media_tag_media_id', $mid);
        $q = $SQL->get(dsn());
        $DB->query($q, 'fetch');
        while($row = $DB->fetch($q)) {
            if ($label) {
                $label = $label . ',' . $row['media_tag_name'];
            } else {
                $label = $row['media_tag_name'];
            }
        }
        return $label;
    }

    public function mediaEagerLoadFromUnit($unit)
    {
        $mediaList = array();
        $mediaDataList = array();
        foreach ($unit as $data) {
            $actualType = $data['type'];
            $type = detectUnitTypeSpecifier($actualType);
            if ($type === 'media') {
                $mediaData = $data['media_id'];
                if (empty($mediaData)) continue;
                $mediaAry = explodeUnitData($mediaData);
                foreach ($mediaAry as $i => $mediaId) {
                    $mediaList[] = $mediaId;
                }
            }
        }
        if ($mediaList) {
            $SQL = SQL::newSelect('media');
            $SQL->addWhereIn('media_id', $mediaList);
            $q = $SQL->get(dsn());
            $DB = DB::singleton(dsn());
            $DB->query($q, 'fetch');
            while ($media = $DB->fetch($q)) {
                $mediaId = intval($media['media_id']);
                $mediaDataList[$mediaId] = $media;
            }
        }
        return $mediaDataList;
    }

    public function getMedia($mid)
    {
        $sql = SQL::newSelect('media');
        $sql->addLeftJoin('blog', 'blog_id', 'media_blog_id');
        $sql->addWhereOpr('media_id', $mid);
        $row = DB::query($sql->get(dsn()), 'row');
        if (empty($row)) {
            return array();
        }
        return array(
            'mid' => $row['media_id'],
            'bid' => $row['media_blog_id'],
            'status' => $row['media_status'],
            'path' => $row['media_path'],
            'thumbnail' => $row['media_thumbnail'],
            'name' => $row['media_file_name'],
            'size' => $row['media_image_size'],
            'filesize' => $row['media_file_size'],
            'type' => $row['media_type'],
            'extension' => $row['media_extension'],
            'original' => $row['media_original'],
            'update_date' => $row['media_update_date'],
            'upload_date' => $row['media_upload_date'],
            'field_1' => $row['media_field_1'],
            'field_2' => $row['media_field_2'],
            'field_3' => $row['media_field_3'],
            'field_4' => $row['media_field_4'],
            'field_5' => $row['media_field_5'],
            'field_6' => $row['media_field_6'],
            'blog_name' => $row['blog_name'],
            'editable' => intval($row['media_user_id']) === SUID
        );
    }

    /**
     * メディアの削除
     *
     * @param $mid
     */
    public function deleteItem($mid)
    {
        $DB = DB::singleton(dsn());
        try {
            if (empty($mid) || !self::canEdit($mid)) {
                throw new \RuntimeException('You are not authorized to delete media.');
            }
            $SQL = SQL::newSelect('media');
            $SQL->addWhereOpr('media_id', $mid);
            $q = $SQL->get(dsn());
            $DB->query($q, 'fetch');
            while ($row = $DB->fetch($q)) {
                $type = $row['media_type'];
                if ($type === 'image') {
                    $path = MEDIA_LIBRARY_DIR . $row['media_path'];
                    $original = MEDIA_LIBRARY_DIR . $row['media_original'];
                    Image::deleteImageAllSize($path);
                    Image::deleteImageAllSize($original);
                } else {
                    self::deleteFile($mid);
                }
            }
            $DB = DB::singleton(dsn());
            $SQL = SQL::newDelete('media');
            $SQL->addWhereOpr('media_id', $mid);
            $DB->query($SQL->get(dsn()), 'exec');

            $SQL = SQL::newDelete('media_tag');
            $SQL->addWhereOpr('media_tag_media_id', $mid);
            $DB->query($SQL->get(dsn()), 'exec');

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * メディアフィールドの挿入
     *
     * @param $Field
     * @param $mediaList
     * @param $useMediaField
     */
    public function injectMediaField($Field, $mediaList, $useMediaField)
    {
        if (empty($mediaList) || empty($useMediaField)) {
            return;
        }
        $useMediaField = array_unique($useMediaField);
        foreach ($useMediaField as $fd) {
            $sourceField = $Field->getArray($fd . '@media');
            $nameAry = [];
            $extensionAry = [];
            $fileSizeAry = [];
            $captionAry = [];
            $linkAry = [];
            $altAry = [];
            $textAry = [];
            $pathAry = [];
            $thumbnailAry = [];
            $pageAry = [];
            $imageSizeAry = [];
            $widthAry = [];
            $heightAry = [];
            $ratioAry = [];
            $focalXAry = [];
            $focalYAry = [];
            $typeAry = [];

            foreach ($sourceField as $i => $mid) {
                if (isset($mediaList[$mid])) {
                    $media = $mediaList[$mid];
                    $path = $this->urlencode($media['media_path']);
                    $type = $media['media_type'];

                    $nameAry[] = $media['media_file_name'];
                    $extensionAry[] = $media['media_extension'];
                    $fileSizeAry[] = $media['media_file_size'];
                    $captionAry[] = $media['media_field_1'];
                    $linkAry[] = $media['media_field_2'];
                    $altAry[] = $media['media_field_3'];
                    $textAry[] = $media['media_field_4'];
                    $pageAry[] = $media['media_field_6'];
                    $typeAry[] = $type;

                    if ($type === 'image') {
                        $pathAry[] = $path;
                        $thumbnailAry[] = $this->getImageThumbnail($path);
                        $imageSizeAry[] = $media['media_image_size'];
                        $focalPoint = $media['media_field_5'];
                        $tmpFocalX = '';
                        $tmpFocalY = '';
                        $width = '';
                        $height = '';
                        $ratio = '';

                        if ($media['media_image_size']) {
                            list($w, $h) = explode('x', $media['media_image_size']);
                            $w = intval(trim($w), 10);
                            $h = intval(trim($h), 10);
                            if ($w > 0 && $h > 0) {
                                $width = $w;
                                $height = $h;
                                $ratio = $w / $h;
                            }
                        }
                        if (strpos($focalPoint, ',') !== false) {
                            list($focalX, $focalY) = explode(',', $focalPoint);
                            if ($focalX && $focalY) {
                                $tmpFocalX = ($focalX / 50) - 1;
                                $tmpFocalY = (($focalY / 50) - 1) * -1;
                            }
                        }
                        $focalXAry[] = $tmpFocalX;
                        $focalYAry[] = $tmpFocalY;
                        $widthAry[] = $width;
                        $heightAry[] = $height;
                        $ratioAry[] = $ratio;
                    } else if ($type === 'svg') {
                        $pathAry[] = $path;
                        $thumbnailAry[] = $this->getSvgThumbnail($path);
                        $imageSizeAry[] = '';
                        $focalXAry[] = '';
                        $focalYAry[] = '';
                        $widthAry[] = '';
                        $heightAry[] = '';
                        $ratioAry[] = '';
                    } else if ($type === 'file') {
                        if (empty($media['media_status'])) {
                            $pathAry[] = $this->getFileOldPermalink($path, false);
                        } else {
                            $pathAry[] = $this->getFilePermalink($mid, false);
                        }
                        if (strtolower($media['media_extension']) === 'pdf' && $media['media_thumbnail']) {
                            $thumbnailAry[] = $this->getPdfThumbnail($media['media_thumbnail']);
                        } else {
                            $thumbnailAry[] = $this->getFileThumbnail($media['media_extension']);
                        }
                        $imageSizeAry[] = '';
                        $focalXAry[] = '';
                        $focalYAry[] = '';
                        $widthAry[] = '';
                        $heightAry[] = '';
                        $ratioAry[] = '';
                    }
                } else {
                    $nameAry[] = '';
                    $extensionAry[] = '';
                    $fileSizeAry[] = '';
                    $captionAry[] = '';
                    $linkAry[] = '';
                    $altAry[] = '';
                    $textAry[] = '';
                    $pathAry[] = '';
                    $thumbnailAry[] = '';
                    $pageAry[] = '';
                    $imageSizeAry[] = '';
                    $focalXAry[] = '';
                    $focalYAry[] = '';
                    $widthAry[] = '';
                    $heightAry[] = '';
                    $ratioAry[] = '';
                    $typeAry[] = '';
                }
            }
            $Field->setField($fd . '@name', $nameAry);
            $Field->setField($fd . '@extension', $extensionAry);
            $Field->setField($fd . '@fileSize', $fileSizeAry);
            $Field->setField($fd . '@caption', $captionAry);
            $Field->setField($fd . '@link', $linkAry);
            $Field->setField($fd . '@alt', $altAry);
            $Field->setField($fd . '@text', $textAry);
            $Field->setField($fd . '@path', $pathAry);
            $Field->setField($fd . '@thumbnail', $thumbnailAry);
            $Field->setField($fd . '@page', $pageAry);
            $Field->setField($fd . '@imageSize', $imageSizeAry);
            $Field->addField($fd . '@focalX', $focalXAry);
            $Field->addField($fd . '@focalY', $focalYAry);
            $Field->addField($fd . '@width', $widthAry);
            $Field->addField($fd . '@height', $heightAry);
            $Field->addField($fd . '@ratio', $ratioAry);
            $Field->addField($fd . '@type', $typeAry);
        }
    }

    /**
     * メディアファイルのダウンロード
     *
     * @param int $mid
     */
    public function downloadFile($mid)
    {
        $media = $this->getMedia($mid);
        $download = new Download($media);
        if (!$download->validate()) {
            httpStatusCode('403 Forbidden Media');
        } else {
            $download->download();
        }
    }
}
