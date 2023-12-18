interface Unit extends JQuery {
  $target: JQuery
  $range: JQuery
}
//-----------
// Edit._add
export default ($unit: Unit) => {
  // targetがなければ実行しない
  if ($unit.$target.size() === 0) {
    return false;
  }

  const { Edit } = ACMS.Dispatch;
  let url = '';

  if (ACMS.Config.admin === 'form2-edit') {
    url = ACMS.Library.acmsLink({ tpl: 'ajax/form-unit-add-list.json' }, true);
  } else {
    url = ACMS.Library.acmsLink({ tpl: 'ajax/unit-add-list.json' }, true);
  }

  $.getJSON(url, (data) => {
    $.each(data.type, (i, type) => {
      let icon = '';
      let className = '';
      if (data.icon && data.icon[i]) {
        icon = data.icon[i];
      }
      if (data.className && data.className[i]) {
        className = data.className[i];
      }
      const $input = $($.parseHTML(`<div class="acms-admin-inline-btn">
        <button type="button" aria-label="${data.label[i]}${ACMS.i18n('entry_editor.add_unit')}" class="${className || 'acms-admin-btn-admin'}">
          ${icon ? `<span class="${icon}"></span>` : ''}
          ${data.label[i]}
        </button>
      </div>`));
      $unit.$target.find('.buttonlist').append($input);

      $input.click(() => {
        let tpl = '';
        if (ACMS.Config.admin === 'form2-edit') {
          tpl = 'ajax/form-unit-add-detail.html';
        } else {
          tpl = 'ajax/unit-add-detail.html';
        }
        const url = ACMS.Library.acmsLink({
          tpl,
          admin: (`entry-add-${type}`),
          Query: {
            hash: Math.random().toString(),
            limit: $unit.find(ACMS.Config.Edit.itemMark).length,
          },
        }, true);

        $.get(url, (html) => {
          if (!html) {
            return;
          }

          const $item = $(ACMS.Config.Edit.itemMark, html);
          const size = $unit.find(':input[name="sort[]"]').last().find('option').size();
          const start = $unit.$target.nextAll(ACMS.Config.Edit.itemMark).size()
            ? parseInt($unit.$target.nextAll(ACMS.Config.Edit.itemMark).first().find(':input[name="sort[]"]').val(), 10)
            : size + 1;
          $item.hide();
          $unit.$target.before($item);
          $item.fadeIn();
          $item.focus();

          // add option to sort
          $unit.find(ACMS.Config.Edit.itemMark).each(function () {
            const $select = $(':input[name="sort[]"]', this);
            const $option = $select.find('option');
            const max = parseInt($option.last().attr('value'), 10);
            const limit = size + $item.size() - $option.size();
            for (let i = 1; i <= limit; i++) {
              const value = max + i;
              $select.append(`<option value="${value}">${value}</option>`);
            }
          });

          // select sort of item
          $item.each(function (i) {
            $(':input[name="sort[]"]', this).val(start + i);
          });

          // select sort of item
          $unit.$target.nextAll(ACMS.Config.Edit.itemMark).each(function () {
            const $sort = $(':input[name="sort[]"]', this);
            $sort.val(parseInt($sort.val(), 10) + $item.size());
          });

          // add option to range
          if ($unit.$range.size()) {
            let max = 0;
            $unit.$range.find('option').each(function () {
              const value = parseInt($(this).val(), 10);
              if (max < value) {
                max = value;
              }
            });
            $unit.$range.find(`option[value="${max}"]`).after(`<option value="${max + 1}">${max + 1}</option>`);
          }

          Edit._refresh($unit);
          Edit.extendTagSelect($item);

          //---------------
          // dispatch item
          if ($('img.column-map', $unit).size()) {
            ACMS.Library.googleLoadProxy('maps', '3', {
              callback() {
                $item.each(function () {
                  Edit._item(this, $unit);
                  ACMS.Dispatch2(this);
                });
              },
              options: {
                region: ACMS.Config.s2dRegion,
              },
            });
          } else {
            $item.each(function () {
              Edit._item(this, $unit);
              ACMS.Dispatch2(this);
            });
          }
        });
      });
    });
  });
};
