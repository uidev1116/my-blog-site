export const DispatchLiteEditorField = (ctx) => {
  const liteEditor = ctx.querySelectorAll(ACMS.Config.LiteEditorMark);
  [].forEach.call(liteEditor, (item) => {
    if ($(item).hasClass('editing')) {
      return;
    }
    $(item).addClass('editing');
    import(
      /* webpackChunkName: "lite-editor-css" */ 'lite-editor/css/lite-editor.css'
    );
    import(/* webpackChunkName: "lite-editor" */ 'lite-editor').then(
      ({ default: LiteEditor }) => {
        new LiteEditor(item, ACMS.Config.LiteEditorFieldConf); // eslint-disable-line no-new
      },
    );
  });
};

const moveCursorToEnd = (el) => {
  let range;
  let selection;
  if (document.createRange) {
    range = document.createRange();
    range.selectNodeContents(el);
    range.collapse(false);
    selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(range);
  } else if (document.selection) {
    range = document.body.createTextRange();
    range.moveToElementText(el);
    range.collapse(false);
    range.select();
  }
};

export default () => {
  ACMS.addListener('acmsAddUnit', (event) => {
    DispatchLiteEditorField(event.obj.item);
    if (ACMS.Config.LiteEditorFeature === true) {
      const { item } = event.obj;
      const selectOptions = [];
      const $select = $('.js-extendTagSelect select', item);
      const selectedOption = $select.val();
      const selectName = $select.attr('name');
      const extendValue = $('[name^="text_extend_tag"]', item).val();
      const sourceModeTags = ACMS.Config.LiteEditorSourceModeTags;

      $('.js-extendTagSelect option', item).each(function () {
        const tag = $(this).val();
        const opt = {
          value: tag,
          label: $(this).text(),
          extendLabel: $(this).data('tag_extend'),
        };
        if (tag === 'wysiwyg') {
          opt.onSelect = (self) => {
            self.data.mode = 'html';
            self.data.hideBtns = true;
            self.data.showSource = true;
            self.data.disableEditorMode = true;
            self.update();
            ACMS.Dispatch.emoditor(
              self._getElementByQuery('[data-selector="lite-editor-source"]'),
            );
          };
        } else if (tag === 'markdown') {
          opt.onSelect = (self) => {
            const editor = $(
              self._getElementByQuery('[data-selector="lite-editor-source"]'),
            ).data('emoditor');
            self.data.mode = 'markdown';
            self.data.disableEditorMode = true;
            self.data.hideBtns = false;
            if (editor) {
              self.data.value = editor.getData();
              editor.destroy();
              $(
                self._getElementByQuery('[data-selector="lite-editor-source"]'),
              ).data('emoditor', null);
              self.data.showSource = true;
              self.update();
            } else if (!self.data.showSource) {
              self.data.showSource = true;
              self.update();
              $(
                self._getElementByQuery('[data-selector="lite-editor-source"]'),
              ).show();
            }
          };
        } else if (tag.match(sourceModeTags)) {
          opt.onSelect = (self) => {
            const editor = $(
              self._getElementByQuery('[data-selector="lite-editor-source"]'),
            ).data('emoditor');
            self.data.mode = 'html';
            self.data.disableEditorMode = true;
            self.data.hideBtns = false;
            if (editor) {
              self.data.value = editor.getData();
              editor.destroy();
              $(
                self._getElementByQuery('[data-selector="lite-editor-source"]'),
              ).data('emoditor', null);
              self.data.showSource = true;
              self.update();
            } else if (!self.data.showSource) {
              self.data.showSource = true;
              self.update();
              $(
                self._getElementByQuery('[data-selector="lite-editor-source"]'),
              ).show();
            }
          };
        } else {
          opt.onSelect = (self) => {
            const emoditor = $(
              self._getElementByQuery('[data-selector="lite-editor-source"]'),
            ).data('emoditor');
            if (emoditor) {
              self.data.value = emoditor.getData();
              emoditor.destroy();
              $(
                self._getElementByQuery('[data-selector="lite-editor-source"]'),
              ).data('emoditor', null);
            }
            self.data.showSource = false;
            self.data.mode = 'html';
            self.data.disableEditorMode = false;
            self.data.hideBtns = false;
            self.update();
            self._getElementByQuery('[data-selector="lite-editor"]').innerHTML = self.data.value;
            $('.entryFormLiteEditor', item).show();
          };
        }
        selectOptions.push(opt);
      });
      import(
        /* webpackChunkName: "lite-editor-emoji-picker-plugin-css" */ 'lite-editor-emoji-picker-plugin/css/lite-editor-emoji-picker.css'
      );
      import(
        /* webpackChunkName: "lite-editor-emoji-picker-plugin" */ 'lite-editor-emoji-picker-plugin'
      ).then(({ default: LiteEditorEmojiPicker }) => {
        $('.entryFormColumnBody', item)
          .not('editing')
          .each(function () {
            const $textarea = $('.entryFormTextarea', this);
            if ($textarea.length === 0) {
              return;
            }
            $(this).addClass('editing');
            const $selector = $("[name^='text_tag']", this);
            const tag = $selector.val();
            const sourceFirst = tag && tag.match(sourceModeTags);
            $textarea.attr('rows', '1');

            const isMobile = (navigator.userAgent.indexOf('iPhone') > 0
                && navigator.userAgent.indexOf('iPad') === -1)
              || navigator.userAgent.indexOf('iPod') > 0
              || navigator.userAgent.indexOf('Android') > 0;
            const liteEditorAry = [];
            $textarea.each((i, textarea) => {
              requestAnimationFrame(async () => {
                const btnOptions = [...ACMS.Config.LiteEditorConf.btnOptions];
                if (
                  ACMS.Config.LiteEditorUseEmojiPicker
                  && ACMS.Config.dbCharset === 'utf8mb4'
                ) {
                  if (!isMobile) {
                    btnOptions.push(
                      new LiteEditorEmojiPicker({
                        label: ACMS.Config.LiteEditorEmojiPickerLabel,
                      }),
                    );
                  }
                }
                const editorOption = {
                  ...ACMS.Config.LiteEditorConf,
                  selectOptions,
                  selectedOption,
                  selectName,
                  extendValue,
                  sourceFirst,
                  mode: selectedOption === 'markdown' ? 'markdown' : 'html',
                };
                editorOption.btnOptions = btnOptions;
                await import(
                  /* webpackChunkName: "lite-editor-css" */ 'lite-editor/css/lite-editor.css'
                );
                const { default: LiteEditor } = await import(
                  /* webpackChunkName: "lite-editor" */ 'lite-editor'
                );
                const editor = new LiteEditor(textarea, editorOption);
                const editable = editor._getElementByQuery(
                  '[data-selector="lite-editor"]',
                );

                const $editInplace = $(item).parents('#js-edit_inplace-box');
                if ($editInplace.length) {
                  editable.focus();
                  moveCursorToEnd(editable);
                  editable.addEventListener('keydown', (e) => {
                    if (
                      e.keyCode === 13
                      && (e.metaKey === true || e.ctrlKey === true)
                    ) {
                      $editInplace.find('#js-edit_inplace-submit').click();
                      return false;
                    }
                  });
                }

                liteEditorAry.push(editor);
                if (sourceFirst) {
                  editor.deactivateEditorMode();
                }
                $(item).data('lite-editor', editor);
                this.focus();
                $('.js-extendTagSelect', item).remove();
                $('.editTextInsert', item).remove();

                // editable.parentElementは<div data-id='hogehoge'></div>の要素
                $('.lite-editor-select', editable.parentElement).on(
                  'change',
                  function () {
                    liteEditorAry.forEach((edit) => {
                      // 自身は除外
                      if (
                        edit._getElementByQuery('.lite-editor-select')
                        === $(this).get(0)
                      ) {
                        return;
                      }
                      edit.e = {
                        target: {
                          value: $(this).val(),
                        },
                      };
                      edit.changeOption();
                    });
                  },
                );
                $(item).on('change', '.lite-editor-extend-input', function () {
                  liteEditorAry.forEach((edit) => {
                    // 自身は除外
                    if (
                      edit._getElementByQuery('.lite-editor-select')
                      === $(this).get(0)
                    ) {
                      return;
                    }
                    edit.data.extendValue = $(this).val();
                    edit.update();
                  });
                });
              });
            });
          });
      });
    }
  });
};
