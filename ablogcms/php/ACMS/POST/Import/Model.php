<?php

class ACMS_POST_Import_Model extends ACMS_POST_Import
{
    protected $data;
    protected $labels;
    protected $csvId;
    protected $nextId;
    protected $isUpdate;
    protected $idLabel;

    function __construct($csv, $labels)
    {
        $this->isUpdate = false;
        $this->labels = $labels;

        foreach ( $labels as $i => $label ) {
            if ( !isset($csv[$i] ) ) {
                throw new RuntimeException('CSVの項目が足りません。');
            }
            if ( $label === $this->idLabel ) {
                $this->csvId = $csv[$i];
            }
            $this->data[$label] = $csv[$i];
        }

        if ( $this->exist() ) {
            $this->isUpdate = true;
        } else {
            $this->nextId();
        }

        $this->validate();
    }

    function exist()
    {
        if ( empty($this->csvId) ) {
            return false;
        }

        return !!ACMS_RAM::entryCode($this->csvId) && ACMS_RAM::entryStatus($this->csvId) !== 'trash';
    }

    function nextId()
    {
        $this->nextId = 0;
    }

    function save($line=false)
    {
        $this->build();

        if ( $this->isUpdate ) {
            $this->update();
        } else {
            $this->insert();
        }
    }

    function validate()
    {

    }

    function build()
    {

    }

    function insert()
    {

    }

    function update()
    {

    }
}
