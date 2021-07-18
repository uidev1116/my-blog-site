<?php

class ACMS_CorrectorBody
{
    /**
     * @var array
     */
    public $const = array();

    public function nl2br($txt)
    {
        return nl2br($txt);
    }

    public function nl2br4html($txt)
    {
        return nl2br($txt, false);
    }

    public function delnl($txt)
    {
        return preg_replace("/(\xe2\x80[\xa8-\xa9]|\xc2\x85|\r\n|\r|\n)/", "", $txt);
    }

    public function escape($txt, $args = array())
    {
        if (!empty($args) and is_array($args)) {
            $rep = array(
                '&' => '&amp;',
                '<' => '&lt;',
                '>' => '&gt;',
                '"' => '&quot;',
                "'" => '&#039;',
            );
            foreach ($args as $val) {
                if (!isset($rep[$val])) {
                    continue;
                }
                unset($rep[$val]);
            }
            return str_replace(array_keys($rep), array_values($rep), $txt);
        } else {
            if (is_array($txt)) {
                $txt = implode($txt);
            }
            return htmlspecialchars($txt, ENT_QUOTES, 'UTF-8');
        }
    }

    public function escvars($txt)
    {
        return str_replace(array('{', '}'), array('&#123;', '&#125;'), $txt);
    }

    public function escquot($txt)
    {
        return preg_replace('@"@', '""', $txt);
    }

    public function trim($txt, $args = array())
    {
        if (!isset($args[0])) {
            return $txt;
        }
        $width = intval($args[0]);
        $marker = isset($args[1]) ? $args[1] : '';

        return mb_strimwidth($txt, 0, $width, $marker);
    }

    public function mb_trim($txt, $args = array())
    {
        if (!isset($args[0])) {
            return $txt;
        }
        $width = intval($args[0]);
        $marker = isset($args[1]) ? $args[1] : '';

        if ($width < mb_strlen($txt)) {
            return mb_substr($txt, 0, $width) . $marker;
        }
        return $txt;
    }

    public function trim4ext($txt, $args = array())
    {
        if (!empty($args[0]) && is_array($args) && preg_match('@^.(.*)$@si', $args[0], $match)) {
            return str_replace($args[0], '', $txt);
        } else {
            return $txt;
        }
    }

    public function table($csv, $args = array())
    {
        if (empty($csv)) {
            return $csv;
        }
        $csv = preg_replace(array('/&gt;(\d+)/', '/&quot;/'), array('>$1', '"'), $csv);

        //-----------
        // overwrite
        $i = 0;
        $m = array();
        foreach (array(
                     'column' => ',',
                     'row' => '[\r\n]+',
                     'enclosure' => '"',
                     'head' => '#',
                     'align' => '\|',
                     'nowrapS' => '\[',
                     'nowrapE' => '\]',
                     'regex' => '@',
                     'rspan' => '\^\d+',
                     'cspan' => '\>\d+',
                 ) as $key => $val) {
            $m[$key] = !empty($args[$i]) ? $args[$i] : $val;
            $i++;
        }

        //--------
        // double
        $doubleCheck = array_unique($m);
        if (count($m) !== count($doubleCheck)) {
            return $csv;
        }

        //-----
        // ptn
        $ptn = $m['regex']
            . '(^|' . $m['column'] . '|' . $m['row'] . ')'
            . '((?:\t| |　|' . $m['rspan'] . '|' . $m['cspan'] . '|' . $m['head'] . '|' . $m['nowrapS'] . '|' . $m['align'] . ')*)'
            . '(?:(?:' . $m['enclosure'] . '((?:[^' . $m['enclosure'] . ']|' . $m['enclosure'] . $m['enclosure'] . ')*)' . $m['enclosure'] . ')|([^' . $m['column'] . ']*?))'
            . '(?=([[:blank:]' . $m['nowrapE'] . $m['align'] . ']*+)(?:' . $m['column'] . '|' . $m['row'] . '|$))'
            . $m['regex'] . 'u';;

        preg_match_all($ptn, $csv, $matches, PREG_SET_ORDER);

        $html = '<tr>';

        while (!!($match = array_shift($matches))) {
            //------------------
            // 1 : delimiter
            // 2 : option left
            // 3 : cell escaped
            // 4 : cell
            // 5 : option right

            $delimiter = $match[1];
            $optionL = $match[2];
            $cell = $match[3] | $match[4];
            $optionR = !empty($match[5]) ? $match[5] : '';

            if (preg_match('@^' . $m['row'] . '$@', $delimiter)) {
                $html .= "</tr>\n<tr>";
            }

            $attr = '';

            //-------
            // align
            if (false !== strpos($optionL, '|')) {
                if (false !== strpos($optionR, '|')) {
                    $attr .= ' style="text-align:center"';
                } else {
                    $attr .= ' style="text-align:left"';
                }
            } else {
                if (false !== strpos($optionR, '|')) {
                    $attr .= ' style="text-align:right"';
                }
            }

            //-------
            // span
            if (preg_match('@\>(\d+)@', $optionL, $ncol)) {
                $col = intval($ncol[1]);
                $attr .= ' colspan="' . $col . '"';
            }
            if (preg_match('@\^(\d+)@', $optionL, $nrow)) {
                $row = intval($nrow[1]);
                $attr .= ' rowspan="' . $row . '"';
            }

            //--------
            // nowrap
            if (false !== strpos($optionL, '[') and false !== strpos($optionR, ']')) {
                $attr .= ' nowrap="nowrap"';
            }
            $optionL = '_' . $optionL . '_';
            $tag = (preg_match('/^_[^#]{0,}?#[^#]{0,}?_/', $optionL)) ? 'th' : 'td';
            $cell = preg_match('/^_[#]{2}?_/', $optionL) ? '#' . $cell : $cell;
            $html .= '<' . $tag . $attr . '>' . nl2br(str_replace('""', '"', $cell)) . '</' . $tag . '>';
        }
        $html .= "</tr>";

        return $html;
    }

    public function definition_list($txt, $args = array())
    {
        if ($lis = preg_split('@( |　|\t)*\r?\n@', $txt, -1, PREG_SPLIT_NO_EMPTY)) {
            $txt = "\n";
            foreach ($lis as $dval) {
                if (preg_match('/^#[^#]/', $dval)) {
                    $txt .= "<dt>" . preg_replace('@^#( |　|\t)*@', '', $dval) . "</dt>\n";
                } else {
                    $txt .= "<dd>" . preg_replace('/##/', '#', $dval) . "</dd>\n";
                }
            }
        }
        return $txt;
    }

    public function acms_corrector_list($txt)
    {
        if ($lis = preg_split('@( |　|\t)*\r?\n@', $txt, -1, PREG_SPLIT_NO_EMPTY)) {
            $txt = "\n<li>" . join("</li>\n<li>", $lis) . "</li>\n";
        }
        return $txt;
    }

    public function markdown($txt, $args = array())
    {
        $lv = intval(isset($args[0]) ? $args[0] : 0);
        if (0 < $lv) {
            $_txt = $txt;
            $txt = '';
            foreach (preg_split('@^@m', $_txt) as $token) {
                if ('#' == substr($token, 0, 1)) {
                    $token = str_repeat('#', $lv) . $token;
                }
                $txt .= $token;
            }
        }

        return \Michelf\MarkdownExtra::defaultTransform($txt);
    }

    public function striptags($txt)
    {
        return strip_tags($txt);
    }

    public function urldecode($txt)
    {
        return urldecode($txt);
    }

    public function urlencode($txt)
    {
        // RFC3986
        return str_replace('%7E', '~', rawurlencode($txt));
    }

    public function html_entity_decode($txt)
    {
        return html_entity_decode($txt);
    }

    public function md5($txt)
    {
        return md5($txt);
    }

    public function base64($txt)
    {
        return base64_encode($txt);
    }

    public function number_format($txt)
    {
        if (!empty($txt) && is_numeric($txt)) {
            return number_format($txt);
        } else {
            return $txt;
        }
    }

    public function str4script($txt)
    {
        return preg_replace(array('@\'|"@', '@\r|\n@'), array('\\\$0', ''), $txt);
    }

    public function tax($txt, $args = array())
    {
        $args[0] = is_numeric($args[0]) ? $args[0] : intval($args[0]);
        return floor($txt * $args[0]);
    }

    public function convert($txt, $args = array())
    {
        return !empty($args[0]) ? mb_convert_kana($txt, strval($args[0])) : $txt;
    }

    public function camelcase_to_hyphen($txt)
    {
        return preg_replace("/([^_])([A-Z])/", "$1-$2", $txt);
    }

    public function symbolfont_path($txt)
    {
        if (in_array($txt, array(
            'Blog_Field',
            'Category_Field',
            'Entry_Field',
            'User_Field',
            'Module_Field',
        ))) {
            return 'entry_body';
        }

        return $this->lowercase($this->camelcase_to_hyphen($txt));
    }

    public function wareki($txt, $args = array())
    {
        $dt = strtotime($this->fixChars($txt));
        if (!$dt) {
            return $txt;
        }
        $ymd = date('Ymd', $dt);
        $y = substr($ymd, 0, 4);

        if ($ymd <= '19120729') {
            $era = '明治';
            $year = $y - 1867;
        } elseif ($ymd >= '19120730' && $ymd <= '19261224') {
            $era = '大正';
            $year = $y - 1911;
        } elseif ($ymd >= '19261225' && $ymd <= '19890107') {
            $era = '昭和';
            $year = $y - 1925;
        } elseif ($ymd >= '19890108' && $ymd <= '20190431') {
            $era = '平成';
            $year = $y - 1988;
        } elseif ($ymd >= '20190501') {
            $era = '令和';
            $year = $y - 2018;
        }

        if (substr($year, 0, 1) == 0) {
            $year = preg_replace('@^0+@', '', $year);
        }
        $result = $era . $year;
        if (isset($args[0])) {
            $result .= $this->datetime($txt, $args);
        }
        return $result;
    }

    public function age($txt)
    {
        $dt = false !== ($dt = strtotime($this->fixChars($txt))) ? $dt : $txt;
        $ymd = date('Ymd', $dt);
        $txt = intval((date('Ymd') - strval($ymd)) / 10000);
        return $txt;
    }

    public function date($txt, $args = array())
    {
        return $this->datetime($txt, $args);
    }

    public function datetime($txt, $args = array())
    {
        if (!isset($args[0])) {
            return $txt;
        }
        $dt = strtotime($this->fixChars($txt));
        if (empty($dt)) {
            return $txt;
        }
        $txt = date($args[0], $dt);
        if ($txt === date($args[0], strtotime(null)) && isset($args[1])) {
            $txt = $args[1];
        }
        return $txt;
    }

    public function resizeImg($src, $args = array())
    {
        return $this->resizeImgBase($src, $args, ImageResize::SCALE_ASPECT_FILL);
    }

    public function resizeImgFit($src, $args = array())
    {
        return $this->resizeImgBase($src, $args, ImageResize::SCALE_ASPECT_FIT);
    }

    public function resizeImgFill($src, $args = array())
    {
        return $this->resizeImgBase($src, $args, ImageResize::SCALE_ASPECT_FILL);
    }

    public function resizeImgBase($src, $args, $mode)
    {
        if (!isset($args[0])) {
            return $src;
        }

        $width = empty($args[0]) ? 0 : intval($args[0]);
        $height = (isset($args[1]) && !empty($args[1])) ? intval($args[1]) : 0;
        $color = isset($args[2]) ? strtolower($args[2]) : 'ffffff';
        $srcPath = $destPath = $destPathVars = '';

        $pfx = 'mode' . $mode . '_';
        if (!empty($width)) {
            $pfx .= 'w' . $width;
        }
        if (!empty($height)) {
            if (!empty($pfx)) {
                $pfx .= '_';
            }
            $pfx .= 'h' . $height;
        }
        if ($color !== 'ffffff') {
            $pfx .= '_' . $color;
        }

        foreach (array('', ARCHIVES_DIR, REVISON_ARCHIVES_DIR, MEDIA_LIBRARY_DIR) as $archive_dir) {
            $tmpPath = $archive_dir . normalSizeImagePath($src);
            $destPath = trim(dirname($tmpPath), '/') . '/' . $pfx . '-' . Storage::mbBasename($tmpPath);
            $destPathVars = trim(dirname($src), '/') . '/' . $pfx . '-' . Storage::mbBasename($tmpPath);
            $largePath = otherSizeImagePath($tmpPath, 'large'); // large path

            if (Storage::isReadable($destPath)) {
                return $destPathVars;
            }
            if (Storage::isReadable($largePath)) {
                $srcPath = $largePath;
                break;
            }
            if (Storage::isReadable($tmpPath)) {
                $srcPath = $tmpPath;
                break;
            }
        }
        if (empty($srcPath)) {
            return $src;
        }
        if (!$xy = Storage::getImageSize($srcPath)) {
            return $src;
        }

        if ($xy[0] < $width) {
            $width = $xy[0];
        }
        if ($xy[1] < $height) {
            $height = $xy[1];
        }

        $image = new ImageResize($srcPath);
        $image = $image->setMode($mode)
            ->setBgColor($color)
            ->setQuality(intval(config('resize_image_jpeg_quality', 100)));
        if (empty($width)) {
            $image = $image->resizeToHeight($height);
        } else {
            if (empty($height)) {
                $image = $image->resizeToWidth($width);
            } else {
                $image = $image->resize($width, $height);
            }
        }
        $image->save($destPath);

        return $destPathVars;
    }

    public function fixChars($txt)
    {
        $needle = array('年', '月', '日', '時', '分', '秒', '　');
        $replacement = array('', '', '', '', '', '', '');
        $txt = mb_convert_kana(str_replace($needle, $replacement, $txt), 'a');
        return $txt;
    }

    public function br4alnum($txt, $args = array())
    {
        if (0
            or !isset($args[0])
            or !($len = intval($args[0]))
        ) {
            return $txt;
        }
        $ptn = '@[[:alnum:]]{' . $len . '}(?=[[:alnum:]])@';
        $br = isset($args[1]) ? $args[1] : '<br />';

        $newText = '';
        while (preg_match($ptn, $txt, $match, PREG_OFFSET_CAPTURE)) {
            $pos = strlen($match[0][0]) + $match[0][1];
            $newText .= substr($txt, 0, $pos) . $br;
            $txt = substr($txt, $pos);
        }
        $newText .= $txt;

        return $newText;
    }

    public function weekEN2JP($txt, $args = array())
    {
        $en = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
        $jp = configArray('week_label');
        foreach ($en as $i => $val) {
            $txt = str_replace($val, $jp[$i], $txt);
        }
        return $txt;
    }

    public function basename($txt)
    {
        return Storage::mbBasename($txt);
    }

    public function dirname($txt)
    {
        return dirname($txt);
    }

    public function zero_padding($txt, $args)
    {
        $length = isset($args[0]) ? intval($args[0]) : 4;
        return str_pad($txt, $length, '0', STR_PAD_LEFT);
    }

    public function convert_bytes($txt, $args)
    {
        if (!empty($args[0]) && preg_match('/^[gmkGMK]$/', $args[0])) {
            return convert_bytes($txt, $args[0], isset($args[1]) ? intval($args[1]) : 2);
        } else {
            return $txt;
        }
    }

    public function align2label($txt)
    {
        $dict = array(
            'auto' => 'おまかせ',
            'center' => '中央',
            'right' => '右寄せ',
            'left' => '左寄せ',
            'hidden' => '非表示',
        );
        return isset($dict[$txt]) ? $dict[$txt] : $txt;
    }

    public function del_pictogram($txt)
    {
        if (!!($path = config('const_file_path')) && empty($this->const)) {
            include SCRIPT_DIR . $path;
            $this->const = $const;
        }
        return str_replace(array_keys($this->const), '', $txt);
    }

    public function split($txt, $args = array())
    {
        if (!isset($args[1])) {
            return $txt;
        }

        $count = intval($args[1]);
        $pattern = isset($args[0]) ? $args[0] : '';
        $data = preg_split('@' . $pattern . '@', $txt);
        if (!isset($data[$count])) {
            return $txt;
        }

        return $data[$count];
    }

    public function contrastColor($color, $args = array())
    {
        $black = isset($args[0]) ? $args[0] : '#000000';
        $white = isset($args[1]) ? $args[1] : '#ffffff';

        return contrastColor($color, $black, $white);
    }

    public function validateUrl($url)
    {
        if (preg_match("/\Ahttps?:\/\//", $url) || preg_match("/\A\//", $url)) {
            return $url;
        } else {
            return "";
        }
    }

    public function lowercase($txt)
    {
        return strtolower($txt);
    }

    public function uppercase($txt)
    {
        return strtoupper($txt);
    }

    public function buildGlobalVars($txt)
    {
        return setGlobalVars($txt);
    }

    public function buildModule($txt)
    {
        return build($txt, Field_Validation::singleton('post'));
    }

    public function buildTpl($txt)
    {
        return $this->buildModule($this->buildGlobalVars($txt));
    }

    public function acmsRam($id, $args)
    {
        $method = isset($args[0]) ? $args[0] : false;
        if ($method) {
            $method = 'ACMS_RAM::' . $method;
            return call_user_func($method, $id);
        }
        return $id;
    }

    public function imageRatioSizeH($src, $args = array())
    {
        foreach (array('', ARCHIVES_DIR, REVISON_ARCHIVES_DIR, MEDIA_LIBRARY_DIR) as $dir) {
            $size = Storage::getImageSize($dir . $src);

            if ($size) {
                $width = isset($args[0]) ? intval($args[0]) : 200;
                list($x, $y) = $size;

                return intval($width * ($y / $x));
            }
        }
        return '';
    }

    public function imageRatioSizeW($src, $args = array())
    {
        foreach (array('', ARCHIVES_DIR, REVISON_ARCHIVES_DIR, MEDIA_LIBRARY_DIR) as $dir) {
            $size = Storage::getImageSize($dir . $src);

            if ($size) {
                $height = isset($args[0]) ? intval($args[0]) : 200;
                list($x, $y) = $size;

                return intval($height * ($x / $y));
            }
        }
        return '';
    }

    public function jsonEscape($txt)
    {
        $escapeTxt = json_encode($txt);
        return mb_substr($escapeTxt, 1, mb_strlen($escapeTxt) - 2);
    }

    public function entryStatusLabel($eid)
    {
        $entry = ACMS_RAM::entry($eid);
        if (empty($entry)) {
            return '';
        }
        $status = $entry['entry_status'];
        $stime = $entry['entry_start_datetime'];
        $etime = $entry['entry_end_datetime'];
        $approval = $entry['entry_approval'];
        $txt = '';

        switch ($status) {
            case 'close':
                $txt = config('admin_entry_title_prefix_close');
                break;
            case 'draft':
                $txt = config('admin_entry_title_prefix_draft');
                break;
            case 'trash':
                if (defined('RVID') && RVID) {
                    $txt = config('admin_entry_title_prefix_trash_approval');
                } else {
                    $txt = config('admin_entry_title_prefix_trash');
                }
                break;
        }
        if ($approval === 'pre_approval') {
            $txt = config('admin_entry_title_prefix_pre_approval');
        }
        if ($stime > date('Y-m-d H:i:s', requestTime())) {
            $txt = config('admin_entry_title_prefix_start');
        }
        if ($etime < date('Y-m-d H:i:s', requestTime())) {
            $txt = config('admin_entry_title_prefix_end');
        }
        return $txt;
    }
}
