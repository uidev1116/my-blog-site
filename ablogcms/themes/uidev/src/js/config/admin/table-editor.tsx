export default {
  mark: '[class^=js-editable-table2]',
  props: {
    mark: {
      align: {
        default: 'left',
        left: 'left',
        center: 'center',
        right: 'right',
      },
      btn: {
        group: 'acms-admin-btn-group acms-admin-btn-group-inline',
        item: 'acms-admin-btn',
        itemActive: 'acms-admin-btn-active',
      },
      icon: {
        alignLeft: <span className="acms-admin-icon-text-left" />,
        alignCenter: <span className="acms-admin-icon-text-center" />,
        alignRight: <span className="acms-admin-icon-text-right" />,
        undo: <span className="acms-admin-icon-undo" />,
        merge: <span className="acms-admin-icon-merge" />,
        split: <span className="acms-admin-icon-split" />,
        td: <span>td</span>,
        th: <span>th</span>,
      },
    },
    btns: [
      {
        tag: 'a',
        className: '',
        icon: <span>リンク</span>,
        tooltip: 'リンク',
      },
      {
        tag: 'em',
        className: '',
        icon: <span>強調</span>,
        tooltip: '強調',
      },
      {
        tag: 'strong',
        className: '',
        icon: <span>重要</span>,
        tooltip: '重要',
      },
    ],
    message: {
      mergeCells: ACMS.i18n('a_table.merge_cell'),
      splitCell: ACMS.i18n('a_table.split_cell'),
      changeToTh: ACMS.i18n('a_table.change_to_th'),
      changeToTd: ACMS.i18n('a_table.change_to_td'),
      alignLeft: ACMS.i18n('a_table.align_left'),
      alignCenter: ACMS.i18n('a_table.align_center'),
      alignRight: ACMS.i18n('a_table.align_right'),
      addColumnLeft: ACMS.i18n('a_table.add_column_left'),
      addColumnRight: ACMS.i18n('a_table.add_column_right'),
      removeColumn: ACMS.i18n('a_table.remove_column'),
      addRowTop: ACMS.i18n('a_table.add_row_top'),
      addRowBottom: ACMS.i18n('a_table.add_row_bottom'),
      removeRow: ACMS.i18n('a_table.remove_row'),
      source: ACMS.i18n('a_table.source'),
      mergeCellError1: ACMS.i18n('a_table.merge_cell_error1'),
      mergeCellConfirm1: ACMS.i18n('a_table.merge_cell_confirm1'),
      pasteError1: ACMS.i18n('a_table.paste_error1'),
      splitError1: ACMS.i18n('a_table.split_error1'),
      splitError2: ACMS.i18n('a_table.split_error2'),
      splitError3: ACMS.i18n('a_table.split_error3'),
      closeLabel: '',
      targetBlank: 'target',
      targetBlankLabel: 'リンク先を別タブで開く',
      addLinkTitle: 'リンク',
      updateLinkTitle: 'リンク',
      addLink: '追加',
      updateLink: '更新',
      removeLink: '削除',
      linkUrl: 'URL',
      linkLabel: 'ラベル',
    },
    showTargetBlankUI: true,
  },
}