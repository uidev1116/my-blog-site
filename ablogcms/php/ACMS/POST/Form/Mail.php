<?php

class ACMS_POST_Form_Mail extends Mail
{
    var $_files     = array();
    
    function addFile ( $path )
    {
        if ( ! Storage::exists($path) ) {
            return false;
        }elseif( Storage::isDirectory($path) ){
            return false;
        }
        $this->_files[] = $path;
        return Storage::mbBasename($path);
    }
    
    function getBody ( )
    {
        $data       = '';

        $encoding   = null;
        $boundary   = null;
        $charset    = null;

        // encoding
        if ( isset($this->_headers['Content-Transfer-Encoding']) ) {
            $encoding   = $this->_headers['Content-Transfer-Encoding']['values']['0'];
        }

        // boundary, charset
        if ( isset($this->_headers['Content-Type']) ) {
            $value  = $this->_headers['Content-Type']['values'][0];
            $params = $this->_headers['Content-Type']['params'];
            if ( preg_match('@^multipart/@', $value) and isset($params['boundary']) ) {
                $boundary   = $params['boundary'];
            } else if ( preg_match('@^text/@', $value) and isset($params['charset']) ) {
                $charset    = $params['charset'];
            }
        }

        foreach ( $this->_bodys as $body ) {
            if ( !empty($data) ) {
                $data   .= $this->_crlf;
            }

            if ( is_object($body) and method_exists($body, 'get') ) {
                $body   = $body->get();
            }

            if ( !empty($boundary) ) {
                $data   .= '--'.$boundary.$this->_crlf;
            } else if ( !empty($charset) ) {
                $body   = $this->covertEncoding($body, $charset, 'UTF-8');
            }

            if ( 'base64' == $encoding ) {
                $body   = join($this->_crlf, str_split(base64_encode($body), 76));
            } else if ( 'quoted-printable' == $encoding ) {
                
            }
            $data   .= $body;
        }
        
        // ファイル添付対応
       foreach ( $this->_files as $file ) {
            $str_body = '';
            $ary = configArray('mail_file_mime');
            
            // config.system.yamlで連想配列を使えない対応
            $ary_mime = array();
            foreach($ary as $key_ary => $val_ary){
                $ary_temp = explode('@',$val_ary);
                $ary_mime[ $ary_temp[0] ] = $ary_temp[1];
            }
            $ary = array();
            
            $ary[0] = $ary_mime;
            
            if( isset( $ary[0] ) ){
                $mime_content_types = $ary[0];
            }
            if ( !empty($file) ) {
                $data   .= $this->_crlf;
            }
            $info    = pathinfo($file);
            // ファイルではない
            if( !isset($info['extension']) ){
                continue;
            }
            $extension  = strtolower(mb_convert_kana($info['extension'], 'a'));
            if ( !isset($mime_content_types[$extension]) ) {
                continue;
            }
            $content    = $mime_content_types[$extension];
            $filename   = Storage::mbBasename($file);
            if ( !$content ) {
                continue;
            }
            
            if ( is_object($file) and method_exists($file, 'get') ) {
                $str_body = $file->get();
                //$body   = chunk_split(base64_encode(Storage::get($file)));
            }else {
                try {
                    $str_body = chunk_split(base64_encode(Storage::get($file))) . "\n";
                } catch ( \Exception $e ) {
                    $str_body = $e->getMessage() . "\n";
                }
            }
            $data .= '--' . $boundary . $this->_crlf;
            $data .= 'Content-Type: ' . $content . '; name="' . $filename . '"' . $this->_crlf;
            $data .= 'Content-Disposition: attachment; filename="' . $filename . '"' . $this->_crlf;
            $data .= 'Content-Transfer-Encoding: base64' . $this->_crlf;
            $data .= $this->_crlf;
            $data .= $str_body;
            
        }
        if( strlen( $boundary ) > 0 ){
            $data   .= $this->_crlf . '--' . $boundary . '--';
        }
        return $data;
    }
}
