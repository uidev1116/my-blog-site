<?php

namespace Acms\Services\RichEditor;

class Helper
{
    public function render($value)
    {
        if (is_string($value)) {
            $value = json_decode($value);
            if ($value && $value->html) {
                return $this->fix($value->html);
            }
        }
        return $value;
    }

    public function renderTitle($value)
    {
        if (is_string($value)) {
            $value = json_decode($value);
            return $value->title;
        }
        return "";
    }

    public function getAttributeMap($attributes, $values)
    {
        $map = [];
        foreach ($attributes as $i => $attribute) {
            //コーテーションを削除
            $map[$attribute] = preg_replace('/[\'\"]/', '', $values[$i]);
        }
        return $map;
    }

    public function getTagFromAttributeMap($map)
    {
        $img = "<img ";
        foreach ($map as $key => $value) {
            $img .= "$key=\"$value\" ";
        }
        $img .= ">";
        return $img;
    }

    public function fix($value)
    {
        $value = preg_replace_callback('/<img(.*?)>/', function ($match) {
            $attrs = [];
            preg_match_all('/(\S+)=[\"\']?((?:.(?![\"\']?\s+(?:\S+)=|[>\"\']))+.)[\"\']?/', $match[1], $attrs);
            $attributes = $attrs[1];
            $values = $attrs[2];
            $map = $this->getAttributeMap($attributes, $values);
            if (empty($map["data-media_id"])) {
                return $match[0];
            }
            $mid = $map["data-media_id"];
            $media = loadMedia($mid);
            $path = '/' . DIR_OFFSET . MEDIA_LIBRARY_DIR . $media->get('path');
            $map["src"] = $path;
            return $this->getTagFromAttributeMap($map);
        }, $value);
        return $value;
    }
}
