<?php

class ACMS_GET_Admin_Unit extends ACMS_GET_Admin_Entry
{
    function buildUnit($data, &$Tpl, $rootBlock = array(), $buildColumn = array())
    {
        return $this->buildColumn($data, $Tpl, $rootBlock, $buildColumn);
    }

    function getColumnDefinition($mode, $type, $i)
    {
        return $this->getColumnDefinition($mode, $type, $i);
    }
}

class ACMS_Model
{
    public $fields;

    /**
     * get
     *
     * @access public
     * @param strig $key
     * @param mixed $default
     *
     * @return mixed
     */
    function get($key, $default = false)
    {
        if (!empty($this->fields[$key])) {
            return $this->fields[$key];
        } else {
            return $default;
        }
    }

    /**
     * set
     *
     * @access public
     * @param string $key
     * @param mixed $val
     *
     * @return void
     */
    function set($key, $val)
    {
        $this->fields[$key] = $val;
    }
}

class ACMS_Model_Unit extends ACMS_Model
{
    /**
     * __construct
     *
     * @access public
     * @param int $utid
     *
     * @return void
     */
    function __construct($utid = null)
    {
        if ($utid != null && is_integer($utid)) {
            $this->load($utid);
        }
    }

    /**
     * load - find unit record by unit id.
     *
     * @access public
     * @param int $utid
     *
     * @return boolean $result
     */
    function load($utid)
    {
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('column');
        $SQL->addWhereOpr('column_id', $utid);
        $row    = $DB->query($SQL->get(dsn()), 'row');

        if (!empty($row)) {
            $this->fields = $row;
            return true;
        } else {
            return false;
        }
    }

    /**
     * setFields - set record fields.
     *
     * @access public
     * @param array $fields
     *
     * @return void
     */
    function setFields($fields)
    {
        $this->fields   = $fields;
    }

    /**
     * getEntryUnits - find unit records by entry id.
     *
     * @access public static
     * @param int $eid
     *
     * @return array|boolean $units|false
     */
    function getEntryUnits($eid)
    {
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('column');
        $SQL->addWhereOpr('column_entry_id', $eid);
        $all    = $DB->query($SQL->get(dsn()), 'all');

        if (empty($all)) {
            return false;
        }

        $units  = array();
        foreach ($all as $row) {
            $Unit = new ACMS_Model_Unit();
            $Unit->setFields($row);
            $units[] = $Unit;
        }

        return $units;
    }

    /**
     * getTypeOfData
     *
     * @access public
     * @param string $type
     *
     * @return array $values
     */
    function getTypeOfData($type)
    {
        switch ($type) {
            case 'text':
                return array(
                    'text'          => $this->get('column_field_1'),
                    'tag'           => $this->get('column_field_2'),
                    'extend_tag'    => $this->get('column_field_3'),
                );
            case 'table':
                return array(
                    'table'     => $this->get('column_field_1'),
                );
            case 'image':
                return array(
                    'caption'   => $this->get('column_field_1'),
                    'path'      => $this->get('column_field_2'),
                    'link'      => $this->get('column_field_3'),
                    'alt'       => $this->get('column_field_4'),
                    'exif'      => $this->get('column_field_6'),
                );
            case 'file':
                return array(
                    'caption'   => $this->get('column_field_1'),
                    'path'      => $this->get('column_field_2'),
                );
            case 'osmap':
            case 'map':
                return array(
                    'msg'   => $this->get('column_field_1'),
                    'lat'   => $this->get('column_field_2'),
                    'lng'   => $this->get('column_field_3'),
                    'zoom'  => $this->get('column_field_4'),
                );
            case 'youtube':
                return array(
                    'youtube_id'    => $this->get('column_field_2'),
                );
            case 'video':
                return array(
                    'video_id'      => $this->get('column_field_2'),
                );
            case 'eximage':
                return array(
                    'caption'   => $this->get('column_field_1'),
                    'normal'    => $this->get('column_field_2'),
                    'large'     => $this->get('column_field_3'),
                    'link'      => $this->get('column_field_4'),
                    'alt'       => $this->get('column_field_5'),
                );
            case 'quote':
                return array(
                    'quote_url' => $this->get('column_field_6'),
                    'html'      => $this->get('column_field_7'),
                    'site_name' => $this->get('collmn_field_1'),
                    'author'    => $this->get('collmn_field_2'),
                    'title'     => $this->get('collmn_field_3'),
                    'description' => $this->get('collmn_field_4'),
                    'image'     => $this->get('collmn_field_5'),
                );
            case 'media':
                return array(
                    'media_id' => $this->get('column_field_1'),
                    'caption' => $this->get('column_field_2'),
                    'alt' => $this->get('column_field_3'),
                    'enlarged' => $this->get('column_field_4'),
                    'use_icon' => $this->get('column_field_5'),
                );
            case 'module':
                return array(
                    'mid'       => $this->get('column_field_1'),
                    'tpl'       => $this->get('column_field_2'),
                );
            case 'custom':
                return array(
                    'field'     => $this->get('column_field_6'),
                );
            case 'rich-editor':
                return array(
                    'json' => $this->get('column_field_1')
                );
            default:
                return array();
        }
    }
}
