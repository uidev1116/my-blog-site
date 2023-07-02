<?php

class ACMS_POST_Form extends ACMS_POST
{
    var $isCacheDelete  = false;

    /**
     * 残った添付ファイルの削除
     * 送信まで行かず確認画面でフォームをキャンセルした場合に添付ファイルが残る為
     *
     * @param int $lifetime
     */
    public static function clearAttachedFile($lifetime=1800)
    {
        $temp_dir = ARCHIVES_DIR . config('mail_attachment_temp_dir', 'temp/');

        $cur    = getcwd();
        Storage::changeDir(SCRIPT_DIR);

        if ( !Storage::isDirectory($temp_dir) ) {
            return;
        }

        $target_dir = opendir($temp_dir);
        while ( $file = readdir($target_dir) ) {
            $path = $temp_dir.$file;

            if ( $file === '.' || $file === '..' ) {
                continue;
            }
            if ( !Storage::isReadable($path) ) {
                continue;
            }
            if ( Storage::isDirectory($path) ) {
                continue;
            }
            $mtime = Storage::lastModified($path);
            if ( REQUEST_TIME - $lifetime > $mtime){
                Storage::remove($path);
                if ( HOOK_ENABLE ) {
                    $Hook = ACMS_Hook::singleton();
                    $Hook->call('mediaDelete', $path);
                }
            }
        }
        closedir($target_dir);
        Storage::changeDir($cur);
    }

    /**
     * フォームのロード
     *
     * @param string $id
     * @return array
     */
    function loadForm($id)
    {
        if ( empty($id) ) { return false; }
        $form   = false;

        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('form');
        $SQL->addWhereOpr('form_code', $id);
        $SQL->addLeftJoin('blog', 'blog_id', 'form_blog_id');
        ACMS_Filter::blogTree($SQL, BID, 'ancestor-or-self');

        $Where  = SQL::newWhere();
        $Where->addWhereOpr('form_blog_id', BID, '=', 'OR');
        $Where->addWhereOpr('form_scope', 'global', '=', 'OR');
        $SQL->addWhere($Where);

        if ( $row = $DB->query($SQL->get(dsn()), 'row') ) {
            $form   = array(
                'id'    => $row['form_id'],
                'bid'   => $row['form_blog_id'],
                'code'  => $row['form_code'],
                'name'  => $row['form_name'],
                'scope' => $row['form_scope'],
                'log'   => $row['form_log'],
                'data'  => unserialize($row['form_data']),
            );
        }

        return $form;
    }

    /**
     * フォームオプション（サーバーサイドのバリデーション）を組み込み
     *
     * @param Field $Option
     * @return array
     */
    function buildOptions($Option)
    {
        $dup = array(); // メールアドレスの重複オプション
        $field = $this->extract('field');

        foreach ( $Option->getArray('field') as $i => $fd ) {
            if ( empty($fd) ) continue;
            if ( !($method = $Option->get('method', '', $i)) ) continue;
            $value  = $Option->get('value', '', $i);
            if ( 'converter' == $method ) {
                $this->Post->set($fd.':converter', $value);
            } elseif ( 'duplication' == $method ) {
                $dup[] = $fd;
                $dup[] = $field->get($fd);
            } else {
                $field->setMethod($fd, $method, $value);
            }
        }
        return $dup;
    }

    /**
     * フォームオプション（サーバーサイドのバリデーション）を組み込み
     *
     * @param Field $Field
     * @param string $fd
     * @return array
     */
    function getAttachedFilePath($Field, $fd)
    {
        if ( 1
            and preg_match('@^(.+)\@path$@', $fd, $match)
            and $Field->isExists($match[1])
            and $Field->isExists($match[1].'@baseName')
        ) {
            $fd_file    = $match[1];
            $filename   = $Field->get($fd_file.'@baseName');
            $path       = $Field->get($fd_file.'@path');
            $original_name = $Field->get($fd_file.'@originalName');

            if ( $download_name = $Field->get($fd_file . '@downloadName') ) {
                $original_name = $download_name;
            }
            if( strlen($filename) === 0 ){
                $filename = Storage::mbBasename($path);
            }
            $realpath = ARCHIVES_DIR.$path;
            $temppath = ARCHIVES_DIR.config('mail_attachment_temp_dir').$filename;

            if ( Storage::exists($realpath) && Storage::isFile($realpath) ) {
                return array(
                    'realpath'  => $realpath,
                    'temppath'  => $temppath,
                    'fieldpath' => config('mail_attachment_temp_dir').$filename,
                    'fdname'    => $fd_file,
                    'original_name' => $original_name,
                );
            }
        }

        return false;
    }

    /**
     * フォームIDの重複チェック
     *
     * @param string $code
     * @param string $fmid
     * @param string $scope
     * @return boolean
     */
    function double($code, $fmid=null, $scope='local')
    {
        if ( empty($code) ) return true;
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('form');
        $SQL->setSelect('form_id');
        $SQL->addLeftJoin('blog', 'blog_id', 'form_blog_id');

        // local
        if ( $scope === 'local' ) {
            ACMS_Filter::blogTree($SQL, BID, 'ancestor-or-self');
            $Where  = SQL::newWhere();
            $Where->addWhereOpr('form_blog_id', BID, '=', 'OR');
            $Where->addWhereOpr('form_scope', 'global', '=', 'OR');
            $SQL->addWhere($Where);
        // global
        } else {
            ACMS_Filter::blogTree($SQL, BID, 'ancestor-or-descendant-or-self');
        }

        $SQL->addWhereOpr('form_code', $code);
        if ( is_int($fmid) ) {
            $SQL->addWhereOpr('form_id', $fmid, '<>');
        }
        $SQL->setLimit(1);

        return !$DB->query($SQL->get(dsn()), 'one');
    }

    /**
     * メールアドレスの重複チェック
     *
     * @param string $code
     * @param string $mail
     * @return boolean
     */
    function mailToDouble($id, $mail)
    {
        $DB     = DB::singleton(dsn());
        $mail   = preg_quote($mail);
        $regex  = "^{$mail}$|[^a-z]{$mail}\s?[>]?[^a-z]";

        $SQL    = SQL::newSelect('log_form');
        $SQL->addSelect('log_form_mail_to');
        $SQL->addWhereOpr('log_form_mail_to', $regex, 'REGEXP');
        $SQL->addWhereOpr('log_form_form_id', $id);
        $SQL->addWhereOpr('log_form_blog_id', BID);

        return !($DB->query($SQL->get(dsn()), 'one'));
    }
}
