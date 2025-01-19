<?php

namespace Acms\Traits\Common;

use Acms\Services\Facades\Application;
use Acms\Services\Facades\Storage;
use Acms\Services\Facades\Database;
use SQL;
use Field;

trait AssetsTrait
{
    /**
     * カスタムフィールド削除時に実態ファイルも削除する
     *
     * @param \Field $field
     * @return void
     */
    public function removeFieldAssetsTrait(Field $field): void
    {
        foreach ($field->listFields() as $fd) {
            if (
                !strpos($fd, '@path') &&
                !strpos($fd, '@tinyPath') &&
                !strpos($fd, '@largePath') &&
                !strpos($fd, '@squarePath')
            ) {
                continue;
            }
            foreach ($field->getArray($fd, true) as $old) {
                $path = ARCHIVES_DIR . $old;
                deleteFile($path);
            }
        }
    }

    /**
     * カスタムフィールド削除時に実態ファイルも削除する
     *
     * @param string[] $filePaths
     * @return void
     */
    public function removeFileAssetsTrait(array $filePaths): void
    {
        foreach ($filePaths as $path) {
            deleteFile(ARCHIVES_DIR . $path);
        }
    }

    /**
     * カスタムフィールド削除時に実態ファイルも削除する
     *
     * @param string[] $imagePaths
     * @return void
     */
    public function removeImageAssetsTrait(array $imagePaths): void
    {
        foreach ($imagePaths as $path) {
            $normal = ARCHIVES_DIR . $path;
            $large = otherSizeImagePath($normal, 'large');
            $tiny = otherSizeImagePath($normal, 'tiny');
            $square = otherSizeImagePath($normal, 'square');
            deleteFile($normal);
            deleteFile($large);
            deleteFile($tiny);
            deleteFile($square);
        }
    }

    /**
     * 画像パスから新しいファイルを作成して、新しいパスのリストを返却
     *
     * @param string[] $imagePaths
     * @return array
     */
    public function duplicateImagesTrait(array $imagePaths): array
    {
        $newImagePaths = [];
        foreach ($imagePaths as $imagePath) {
            $fullpath = ARCHIVES_DIR . $imagePath;
            $newFullpath = $this->createUniqueFilepathTrait($fullpath);

            $largeFullpath = otherSizeImagePath($fullpath, 'large');
            $tinyFullpath = otherSizeImagePath($fullpath, 'tiny');
            $squareFullpath = otherSizeImagePath($fullpath, 'square');

            $newLargeFullpath = otherSizeImagePath($newFullpath, 'large');
            $newTinyFullpath = otherSizeImagePath($newFullpath, 'tiny');
            $newSquareFullpath = otherSizeImagePath($newFullpath, 'square');
            if (Storage::isReadable($fullpath)) {
                copyFile($fullpath, $newFullpath);
            }
            if (Storage::isReadable($largeFullpath)) {
                copyFile($largeFullpath, $newLargeFullpath);
            }
            if (Storage::isReadable($tinyFullpath)) {
                copyFile($tinyFullpath, $newTinyFullpath);
            }
            if (Storage::isReadable($squareFullpath)) {
                copyFile($squareFullpath, $newSquareFullpath);
            }
            $newImagePaths[] = substr($newFullpath, strlen(ARCHIVES_DIR));
        }
        return $newImagePaths;
    }

    /**
     * ファイルパスから新しいファイルを作成して、新しいパスのリストを返却
     *
     * @param string[] $filePaths
     * @return array
     */
    public function duplicateFilesTrait(array $filePaths): array
    {
        $newFilePaths = [];
        foreach ($filePaths as $filePath) {
            $fullpath = ARCHIVES_DIR . $filePath;
            $newFullpath = $this->createUniqueFilepathTrait($fullpath);
            if (Storage::isReadable($fullpath)) {
                copyFile($fullpath, $newFullpath);
            }
            $newFilePaths[] = substr($newFullpath, strlen(ARCHIVES_DIR));
        }
        return $newFilePaths;
    }

    /**
     * カスタムフィールド複製時に実態ファイルも複製する
     *
     * @param \Field $field
     * @return void
     */
    public function duplicateFieldsTrait(Field $field): void
    {
        foreach ($field->listFields() as $fd) {
            if (preg_match('/(.*?)@path$/', $fd, $match)) {
                $fieldBase = $match[1];
                $set = false;
                foreach ($field->getArray("{$fieldBase}@path") as $i => $path) {
                    $fullpath = ARCHIVES_DIR . $path;
                    if (!Storage::isFile($fullpath)) {
                        if ($i === 0) {
                            $field->deleteField("{$fieldBase}@path");
                            $field->deleteField("{$fieldBase}@largePath");
                            $field->deleteField("{$fieldBase}@tinyPath");
                            $field->deleteField("{$fieldBase}@squarePath");
                        }
                        $field->addField("{$fieldBase}@path", '');
                        $field->addField("{$fieldBase}@largePath", '');
                        $field->addField("{$fieldBase}@tinyPath", '');
                        $field->addField("{$fieldBase}@squarePath", '');

                        continue;
                    }
                    if (!$set) {
                        $field->delete("{$fieldBase}@path");
                        $field->delete("{$fieldBase}@largePath");
                        $field->delete("{$fieldBase}@tinyPath");
                        $field->delete("{$fieldBase}@squarePath");
                        $set = true;
                    }
                    $info = pathinfo($path);
                    $dirname = empty($info['dirname']) ? '' : $info['dirname'] . '/';
                    Storage::makeDirectory(ARCHIVES_DIR . $dirname);

                    $largeFullpath = otherSizeImagePath($fullpath, 'large');
                    $tinyFullpath = otherSizeImagePath($fullpath, 'tiny');
                    $squareFullpath = otherSizeImagePath($fullpath, 'square');

                    $newFullpath = $this->createUniqueFilepathTrait($fullpath);
                    $newLargeFullpath = otherSizeImagePath($newFullpath, 'large');
                    $newTinyFullpath = otherSizeImagePath($newFullpath, 'tiny');
                    $newSquareFullpath = otherSizeImagePath($newFullpath, 'square');

                    if (Storage::isReadable($fullpath)) {
                        copyFile($fullpath, $newFullpath);
                        $newPath = substr($newFullpath, strlen(ARCHIVES_DIR));
                        $field->add("{$fieldBase}@path", $newPath);
                    }
                    if (Storage::isReadable($largeFullpath)) {
                        copyFile($largeFullpath, $newLargeFullpath);
                        $newLargePath = substr($newLargeFullpath, strlen(ARCHIVES_DIR));
                        $field->add("{$fieldBase}@largePath", $newLargePath);
                    }
                    if (Storage::isReadable($tinyFullpath)) {
                        copyFile($tinyFullpath, $newTinyFullpath);
                        $newTinyPath = substr($newTinyFullpath, strlen(ARCHIVES_DIR));
                        $field->add("{$fieldBase}@tinyPath", $newTinyPath);
                    }
                    if (Storage::isReadable($squareFullpath)) {
                        copyFile($squareFullpath, $newSquareFullpath);
                        $newSquarePath = substr($newSquareFullpath, strlen(ARCHIVES_DIR));
                        $field->add("{$fieldBase}@squarePath", $newSquarePath);
                    }
                }
            }
        }
    }

    /**
     * ユニットの削除指定されたパスがDBに存在するかチェック
     *
     * @param string $type
     * @param string $path
     * @return bool
     */
    public function validateRemovePath(string $type, string $path): bool
    {
        /** @var \Acms\Services\Unit\Contracts\Model[] $oldUnitData */
        static $oldUnitData = [];

        /** @var \Acms\Services\Unit\Repository $unitRepository */
        $unitRepository = Application::make('unit-repository');

        if (empty($oldUnitData)) {
            $unitIds = [];
            if (is_array($_POST['type'])) {
                foreach (array_keys($_POST['type']) as $i) {
                    $unitIds[] = intval($_POST['clid'][$i]);
                }
            }
            $unitData = [];
            foreach (['column', 'column_rev'] as $table) {
                $sql = SQL::newSelect($table);
                $sql->addWhereIn('column_id', $unitIds);
                if ($data = Database::query($sql->get(dsn()), 'all')) {
                    $unitData = array_merge($data, $unitData);
                }
            }
            $oldUnitData = $unitRepository->loadModels($unitData);
        }
        foreach ($oldUnitData as $unit) {
            if ($unit->getUnitType() === $type && $unit instanceof \Acms\Services\Unit\Contracts\ValidatePath) {
                $paths = $unit->getFilePaths();
                if (in_array($path, $paths, true)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 複製時に衝突しないファイル名を生成する
     *
     * @param string $path ファイルパス
     * @return string 衝突しないファイルパス
     */
    private function createUniqueFilepathTrait(string $path): string
    {
        if (config('entry_duplicate_random_filename') !== 'off') {
            $fileinfo = pathinfo($path);
            return $fileinfo['dirname'] . '/' . uniqueString() . '.' . $fileinfo['extension'];
        }
        return Storage::uniqueFilePath($path);
    }
}
