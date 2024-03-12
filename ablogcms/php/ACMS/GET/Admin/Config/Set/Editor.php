<?php

class ACMS_GET_Admin_Config_Set_Editor extends ACMS_GET_Admin_Config_Set_Index
{
    /**
     * コンフィグセットのタイプ
     * @var string
     */
    protected $type = 'editor';

    /**
     * 編集ページ
     *
     * @var string
     */
    protected $editPage = 'config_set_editor_edit';

    /**
     * コンフィグ一覧
     *
     * @var string
     */
    protected $configPage = 'config_editor';
}
