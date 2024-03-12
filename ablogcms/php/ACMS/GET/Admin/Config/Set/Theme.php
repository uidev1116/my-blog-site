<?php

class ACMS_GET_Admin_Config_Set_Theme extends ACMS_GET_Admin_Config_Set_Index
{
    /**
     * コンフィグセットのタイプ
     * @var string
     */
    protected $type = 'theme';

    /**
     * 編集ページ
     *
     * @var string
     */
    protected $editPage = 'config_set_theme_edit';

    /**
     * コンフィグ一覧
     *
     * @var string
     */
    protected $configPage = 'config_theme';
}
