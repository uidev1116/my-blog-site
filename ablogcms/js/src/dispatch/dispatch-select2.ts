const dispatchSelect2 = (context: Element | Document = document) => {
  const selects = context.querySelectorAll<HTMLSelectElement>(ACMS.Config.select2Mark);
  selects.forEach((select) => {
    const option = {
      containerCssClass: 'acms-admin-selectbox',
      dropdownCssClass: 'acms-admin-select-dropdown',
    };
    const options = select.querySelectorAll<HTMLOptionElement>('option');
    if (options.length >= ACMS.Config.select2Threshold) {
      import(/* webpackChunkName: "select2" */ '../lib/select2').then(({ default: select2 }) => {
        select2(select, option);
      });
    }
  });
};

export default dispatchSelect2;
