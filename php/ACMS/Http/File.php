<?php

/**
 * ACMS_Http_File
 */
class ACMS_Http_File extends ACMS_Http
{
    protected $name;
    protected $type;
    protected $path;
    protected $extension;

    public function __construct($name)
    {
        $this->key = $name;
        $this->validate();
        $this->name = $_FILES[$name]['name'];
        $this->type = $_FILES[$name]['type'];
        $this->size = $_FILES[$name]['size'];
        $this->path = $_FILES[$name]['tmp_name'];
    }

    /**
     * getter path
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * getter path
     *
     * @return int
     */
    public function getFileSize()
    {
        return $this->size;
    }

    /**
     * getter path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * アップロードファイルの検証
     *
     */
    private function validate()
    {
        // 未定義である・複数ファイルである・$_FILES Corruption 攻撃をチェック
        if (
            !isset($_FILES[$this->key]['error']) ||
            !is_int($_FILES[$this->key]['error']) ||
            !isset($_FILES[$this->key]['tmp_name'])
        ) {
            throw new \RuntimeException(gettext('パラメータが不正です'));
        }

        // $_FILES['upfile']['error'] の値を確認
        switch ( $_FILES[$this->key]['error'] ) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new \RuntimeException(UPLOAD_ERR_NO_FILE);
            case UPLOAD_ERR_INI_SIZE:
                throw new \RuntimeException(UPLOAD_ERR_INI_SIZE);
            case UPLOAD_ERR_FORM_SIZE:
                throw new \RuntimeException(UPLOAD_ERR_FORM_SIZE);
            default:
                throw new \RuntimeException($_FILES[$this->key]['error']);
        }
    }

    /**
     * フォーマットのチェック
     *
     * @param array $mimeTypes
     */
    public function validateFormat($mimeTypes=array())
    {
        // 許可ファイル拡張子をまとめておく
        if ( empty($mimeTypes) ) {
            $mimeTypes = array_merge(
                configArray('file_extension_document'),
                configArray('file_extension_archive'),
                configArray('file_extension_movie'),
                configArray('file_extension_audio')
            );
        }

        // MIMEタイプに対応する拡張子を自前で取得する
        if ( array_search(
            mime_content_type($_FILES[$this->key]['tmp_name']),
            $mimeTypes,
            true
        ) === FALSE ) {
            throw new RuntimeException(gettext('ファイル形式が不正です'));
        }
    }

    /**
     * 文字コードの変換
     *
     * @param string $path
     * @return SplTempFileObject
     */
    public function convertEncoding($path)
    {
        try {
            $data = mb_convert_encoding(Storage::get($path), 'UTF-8', 'UTF-8, SJIS-win, SJIS');
            $data = preg_replace('/^\xEF\xBB\xBF/', '', $data); // remove BOM

            $temp = new SplTempFileObject();
            $temp->fwrite($data);
            $temp->rewind();

            return $temp;
        } catch ( \Exception $e ) {
            return new SplTempFileObject();
        }
    }

    /**
     * ファイルからCSVオブジェクトの取得
     *
     * @return SplTempFileObject
     */
    public function getCsv()
    {
        $this->validateFormat(array('csv', 'text/csv', 'text/plain', 'html', 'text/html', 'application/csv'));
        $path = $this->getPath();

        $csv = $this->convertEncoding($path);
        $csv->setFlags(
            SplFileObject::READ_CSV |
            SplFileObject::READ_AHEAD |
            SplFileObject::SKIP_EMPTY
        );

        return $csv;
    }
}
