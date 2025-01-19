const dispatchLiteEditorField = (context) => {
  const liteEditor = context.querySelectorAll(ACMS.Config.LiteEditorMark);
  [].forEach.call(liteEditor, (item) => {
    if (item.closest(ACMS.Config.fieldgroupSortableItemTemplateMark)) {
      return;
    }
    if ($(item).hasClass('editing')) {
      return;
    }
    $(item).addClass('editing');
    import(/* webpackChunkName: "lite-editor-css" */ 'lite-editor/css/lite-editor.css');
    import(/* webpackChunkName: "lite-editor" */ 'lite-editor').then(({ default: LiteEditor }) => {
      new LiteEditor(item, ACMS.Config.LiteEditorFieldConf); // eslint-disable-line no-new
    });
  });
};

export default dispatchLiteEditorField;
