<?php

class ACMS_POST_Fix_Tag_Preview extends ACMS_POST_Fix_Tag
{
    var $preview = array();

    function init()
    {
        $this->Post->set('preview', 'on');
    }

    function process($data, $word)
    {
        $this->preview[$word][] = array(
            'title'     => $data['entry_title'],
            'fulltext'  => substr($data['fulltext_value'], 0, 512),
            'word'      => $word,
            'eid'       => $data['entry_id'],
        );
    }

    function success()
    {
        $i = 0;
        foreach ( $this->preview as $word => $data ) {
            $$word = new Field_Validation();
            foreach ( $data as $val ) {
                foreach ( $val as $k => $v ) {
                    $$word->add($k, $v);
                }
            }
            $this->Post->addChild('data'.$i, $$word);
            $this->Post->add('word', $word);

            $i++;
        }
    }
}
