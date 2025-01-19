const dispatchAtable = (context = document) => {
  if (!context) {
    return;
  }
  import(/* webpackChunkName: "a-table-css" */ 'a-table/css/a-table.css');
  import(/* webpackChunkName: "a-table" */ 'a-table').then(({ default: Atable }) => {
    const editor = context.querySelectorAll(`${ACMS.Config.aTableMark}:not(.editing)`);
    [].forEach.call(editor, (box) => {
      const dest = box.querySelector(ACMS.Config.aTableDestMark);
      const elem = box.querySelector('table');
      if (!elem) {
        return;
      }
      if (box.classList.contains('editing')) {
        return;
      }
      const table = new Atable(elem, {
        mark: ACMS.Config.aTableConf,
        selector: {
          option: ACMS.Config.aTableSelector,
        },
        tableOption: ACMS.Config.aTableOption,
        message: ACMS.Config.aTableMessage,
      });
      table.afterRendered = () => {
        dest.value = table.getTable();
      };
      table.afterEntered = () => {
        dest.value = table.getTable();
      };
      table.afterRendered();
      box.classList.add('editing');
    });
  });
};

export default dispatchAtable;
