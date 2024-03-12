<?php

class ACMS_User_GET_Template_VarsTable extends ACMS_GET
{
    function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        preg_match_all('/<!--@doc([^>]*?)-->/sm', $this->tpl, $matches, PREG_SET_ORDER);

        if (empty($matches)) {
            $Tpl->add('notFound');
            return $Tpl->get();
        }

        foreach ($matches as $match) {
            $comment = $match[1];

            $id     = $this->getTemplateId($comment);
            $params = $this->getParam($comment);
            $text   = $this->getText($comment);
            $paramStr = ' ';

            foreach ($params as $param) {
                $Tpl->add(array('param:loop', 'comment:loop'), $param);
                $paramStr .= $param['param'] . '="" ';
            }

            $annotation = array(
                'id' => $id,
                'numberOfParam' => count($params),
                'comment' => $text,
                'preview' => "<!-- GET_Template id=\"$id\" -->",
                'snippet' => "<!-- GET_Template id=\"$id\"$paramStr-->",
            );

            if ($author = $this->getAuthor($comment)) {
                $annotation['author'] = $author;
            }
            if ($create = $this->getCreate($comment)) {
                $annotation['create'] = $create;
            }

            $Tpl->add('comment:loop', $annotation);
            $Tpl->add('search:loop', array(
                'label' => $id,
                'link'  => '#' . $id,
            ));
        }

        return $Tpl->get();
    }

    function getAnnotation($annotation, $comment)
    {
        $pattern = '/@' . $annotation . '(?:[\t 　]+)(.*)/i';
        if (preg_match($pattern, $comment, $match)) {
            return $match[1];
        }
        return false;
    }

    function getText($comment)
    {
        $comment = preg_replace('/^(.*)@(.*)$/m', '', $comment);
        $comment = preg_replace('/^[\t\s]*#[\t\s]*/m', '', $comment);

        return $comment;
    }

    function getTemplateId($comment)
    {
        return $this->getAnnotation('id', $comment);
    }

    function getAuthor($comment)
    {
        return $this->getAnnotation('author', $comment);
    }

    function getCreate($comment)
    {
        return $this->getAnnotation('create', $comment);
    }

    function getParam($comment)
    {
        $params = array();

        if (preg_match_all('/@param(?:[\t 　]+)([^\|]*)(?:[\t\s]*)(?:\|?)(?:[\t\s]*)(.*)/i', $comment, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $params[] = array(
                    'param' => trim($match[1]),
                    'label' => trim($match[2]),
                );
            }
        }
        return $params;
    }
}
