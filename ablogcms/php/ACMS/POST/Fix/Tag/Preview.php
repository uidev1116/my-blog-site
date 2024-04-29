<?php

class ACMS_POST_Fix_Tag_Preview extends ACMS_POST_Fix_Tag
{
    public $preview = [];

    protected function init()
    {
        $this->Post->set('preview', 'on');
    }

    protected function process($data, $word)
    {
        $this->preview[$word][] = [
            'title'     => $data['entry_title'],
            'fulltext'  => substr($data['fulltext_value'], 0, 512),
            'word'      => $word,
            'eid'       => $data['entry_id'],
        ];
    }

    protected function success()
    {
        $i = 0;
        foreach ($this->preview as $word => $data) {
            $field = new Field_Validation();
            foreach ($data as $val) {
                foreach ($val as $k => $v) {
                    $field->add($k, $v);
                }
            }
            $this->Post->addChild('data' . $i, $field);
            $this->Post->add('word', $word);

            $i++;
        }
    }
}
