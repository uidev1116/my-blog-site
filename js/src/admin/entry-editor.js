import React from 'react';
import Expand from 'ui-expand';
import { render, unmountComponentAtNode } from 'react-dom';
import DispatchLiteEditor, { DispatchLiteEditorField } from '../admin/lite-editor';
import { addClass, hasClass } from '../lib/dom';

/**
 * カテゴリー選択
 */
const dispatchCategorySelect = () => {
  const categorySelectTargetAll = document.querySelectorAll('.js-admin-category-select');
  if (categorySelectTargetAll.length > 0) {
    import(/* webpackChunkName: "category-select" */'../components/category-select').then(({ default: CategorySelect }) => {
      [].forEach.call(categorySelectTargetAll, (categorySelectTarget) => {
        const target = categorySelectTarget.querySelector('.js-target');
        const value = categorySelectTarget.querySelector('.js-value');
        const value2 = categorySelectTarget.querySelectorAll('.js-value2');

        render(<CategorySelect
          creation={categorySelectTarget.getAttribute('data-creation') === 'true'}
          noneOption={categorySelectTarget.getAttribute('data-none') === 'true'}
          targetDom={value}
          etcTargetDoms={value2}
        />, target);
      });
    });
  }
};

/**
 * タグ選択
 */
const dispatchTagSelect = () => {
  const tagSelectTarget = document.querySelector('.js-admin-tag-select');
  if (!tagSelectTarget) {
    return;
  }
  import(/* webpackChunkName: "rich-select" */'../components/rich-select').then(({ default: RichSelect }) => {
    const target = tagSelectTarget.querySelector('.js-target');
    const value = tagSelectTarget.querySelector('.js-value');
    const endpoint = ACMS.Library.acmsLink({
      bid: ACMS.Config.bid,
      tpl: 'ajax/edit/tag-assist.json'
    }, false);
    const options = [];

    if (value) {
      const tags = value.value.split(',');
      tags.forEach((tag) => {
        tag = tag.trim();
        if (tag) {
          options.push({
            value: tag,
            label: tag
          });
        }
      });
    }
    render(<RichSelect
      dataUrl={endpoint}
      defaultValue={options}
      className="admin-admin-tag-select"
      isMulti
      creatable
      closeOnSelect={false}
      placeholder={ACMS.i18n('entry_editor.tag_placeholder')}
      noResultsText={ACMS.i18n('entry_editor.tag_notfound')}
      promptTextCreator={label => ACMS.i18n('entry_editor.add_tag').replace('$1', label)}
      isValidNewOption={({ label }) => !!label}
      onChange={(data) => {
        const list = [];
        data.forEach((item) => {
          list.push(item.value);
        });
        value.value = list.join(',');
      }}
    />, target);
  });
};

/**
 * サブカテゴリー選択
 */
const dispatchSubCategorySelect = (ignoreCid = 0) => {
  const tagSelectTarget = document.querySelector('.js-admin-sub-category-select');
  if (!tagSelectTarget) {
    return;
  }
  import(/* webpackChunkName: "rich-select" */'../components/rich-select').then(({ default: RichSelect }) => {
    const target = tagSelectTarget.querySelector('.js-target');
    const value = tagSelectTarget.querySelector('.js-value');
    const endpoint = ACMS.Library.acmsLink({
      bid: ACMS.Config.bid,
      cid: ACMS.Config.cid,
      tpl: 'ajax/edit/category-assist.json'
    }, false);
    const options = [];

    if (value) {
      const ids = value.value.split(',');
      const labels = value.getAttribute('data-label').split(',');
      const l = Math.min(ids.length, labels.length);

      for (let i = 0; i < l; i++) {
        if (!ids[i] || !labels[i]) {
          continue;
        }
        const cid = parseInt(ids[i], 10);
        if (cid === ignoreCid) {
          continue;
        }
        options.push({
          value: cid,
          label: labels[i]
        });
      }
    }
    unmountComponentAtNode(target);
    render(<RichSelect
      dataUrl={endpoint}
      defaultValue={options}
      className="admin-admin-tag-select"
      isMulti
      creatable={false}
      closeOnSelect={false}
      ignoreOptions={[ignoreCid]}
      placeholder={ACMS.i18n('entry_editor.subcategory_placeholder')}
      noResultsText={ACMS.i18n('entry_editor.subcategory_notfound')}
      onChange={(data) => {
        const list = [];
        data.forEach((item) => {
          list.push(item.value);
        });
        value.value = list.join(',');
      }}
      filterOption={(option, filter) => {
        if (option.value === ignoreCid) {
          return false;
        }
        if (!filter) {
          return true;
        }
        if (option.label.indexOf(filter) !== -1) {
          return true;
        }
        return false;
      }}
    />, target);
  });
};

/**
 * Table Editor
 */
const dispatchAtable = (item) => {
  if (!item) {
    return;
  }
  import(/* webpackChunkName: "a-table-css" */'a-table/css/a-table.css');
  import(/* webpackChunkName: "a-table" */'a-table').then(({ default: Atable }) => {
    const editor = item.querySelectorAll(`${ACMS.Config.aTableMark}:not(.editing)`);
    [].forEach.call(editor, (box) => {
      const dest = box.querySelector(ACMS.Config.aTableDestMark);
      const elem = box.querySelector('table');
      if (!elem) {
        return;
      }
      if (hasClass(box, 'editing')) {
        return;
      }
      const table = new Atable(elem, {
        mark: ACMS.Config.aTableConf,
        selector: {
          option: ACMS.Config.aTableSelector
        },
        tableOption: ACMS.Config.aTableOption,
        message: ACMS.Config.aTableMessage
      });
      table.afterRendered = () => {
        dest.value = table.getTable();
      };
      table.afterEntered = () => {
        dest.value = table.getTable();
      };
      table.afterRendered();
      addClass(box, 'editing');
    });
  });
};

/**
 * 関連エントリ
 */
const dispatchRelatedEntrySearch = () => {
  if (!document.querySelector('.js-related-entry')) {
    return;
  }
  import(/* webpackChunkName: "related-entries" */'../components/related-entries').then(({ default: RelatedEntries }) => {
    const relatedEntries = document.querySelectorAll('.js-related-entry');
    [].forEach.call(relatedEntries, (relatedEntry) => {
      if (hasClass(relatedEntry, 'js-related-entry-ready')) {
        return;
      }
      addClass(relatedEntry, 'js-related-entry-ready');
      const items = [];
      const type = relatedEntry.getAttribute('data-type');
      const title = relatedEntry.getAttribute('data-title');
      const moduleId = relatedEntry.getAttribute('data-module-id');
      const ctx = relatedEntry.getAttribute('data-ctx');
      const maxItem = parseInt(relatedEntry.getAttribute('data-max-item'), 10) || 0;
      $('.js-related_entry_item', relatedEntry).each((i, item) => {
        items.push({
          id: $(item).data('id'),
          title: $(item).data('title'),
          image: $(item).data('image'),
          categoryName: $(item).data('category-name'),
          url: $(item).data('url')
        });
      });
      render(<RelatedEntries
        items={items}
        type={type}
        title={title}
        moduleId={moduleId}
        ctx={ctx}
        maxItem={maxItem}
      />, relatedEntry);
    });
  });
};

/**
 * サブカテゴリー選択
 */
const dispatchSubCategorySelector = () => {
  dispatchSubCategorySelect(parseInt(ACMS.Config.cid, 10));
  const mainCategory = document.querySelector('[name=category_id]');
  if (mainCategory) {
    mainCategory.addEventListener('change', (event) => {
      if (event.currentTarget.value) {
        dispatchSubCategorySelect(parseInt(event.currentTarget.value, 10));
      }
    });
  }
};

export default () => {
  /**
   * カテゴリー選択
   */
  ACMS.Dispatch.categorySelect = dispatchCategorySelect;
  dispatchCategorySelect();
  ACMS.addListener('acmsAdminDelayedContents', () => {
    dispatchCategorySelect();
  });

  /**
   * タグ選択
   */
  dispatchTagSelect();
  ACMS.addListener('acmsAdminDelayedContents', () => {
    dispatchTagSelect();
  });

  /**
   * サブカテゴリー選択
   */
  dispatchSubCategorySelector();
  ACMS.addListener('acmsAdminDelayedContents', () => {
    dispatchSubCategorySelector();
  });

  /**
   * a-table
   */
  const dispatchATableField = (ctx) => {
    const tables = ctx.querySelectorAll(ACMS.Config.aTableFieldMark);
    [].forEach.call(tables, (table) => {
      dispatchAtable(table);
    });
  };
  dispatchATableField(document);
  ACMS.addListener('acmsAdminDelayedContents', () => {
    dispatchATableField(document);
  });
  ACMS.addListener('acmsAddUnit', (event) => {
    if (event.obj.item && event.obj.item.querySelector) {
      const tables = event.obj.item.querySelectorAll('.js-table-unit');
      [].forEach.call(tables, (table) => {
        dispatchAtable(table);
      });
    }
  });
  ACMS.addListener('acmsCustomFieldMakerPreview', (event) => {
    dispatchATableField(event.target);
  });

  /**
   * SmartBlock
   */

  const dispatchSmartBlock = (ctx) => {
    const smartBlocks = ctx.querySelectorAll(ACMS.Config.SmartBlockMark);
    [].forEach.call(smartBlocks, (item) => {
      import(/* webpackChunkName: "smartblock-dispatch" */'../dispatch/smartblock')
        .then(({ default: SmartBlock }) => {
          SmartBlock(item);
        });
    });
    //--------------------
    // smartblock-expand
    const expands = ctx.querySelectorAll('.js-expand');
    new Expand(expands, { // eslint-disable-line no-new
      beforeOpen: (element) => {
        $(element).addClass('js-acms-expanding');
      },
      onOpen: (element) => {
        $('.js-expand-icon', element)
          .removeClass('acms-admin-icon-expand-arrow')
          .addClass('acms-admin-icon-contract-arrow');
        $(element).addClass('js-acms-expanded');
      },
      onClose: (element) => {
        $(element).removeClass('js-acms-expanding');
        $(element).removeClass('js-acms-expanded');
        $('.js-expand-icon', element)
          .addClass('acms-admin-icon-expand-arrow')
          .removeClass('acms-admin-icon-contract-arrow');
      }
    });
    [].forEach.call(expands, (extend) => {
      $(extend).removeClass('js-expand');
    });
  };

  dispatchSmartBlock(document);
  ACMS.addListener('acmsAddUnit', (event) => {
    dispatchSmartBlock(event.obj.item);
  });
  ACMS.addListener('acmsAdminDelayedContents', (e) => {
    const ctx = e.target || document;
    dispatchSmartBlock(ctx);
  });
  ACMS.addListener('acmsCustomFieldMakerPreview', (event) => {
    dispatchSmartBlock(event.target);
  });

  /**
   * 関連エントリー
   */
  dispatchRelatedEntrySearch();
  ACMS.addListener('acmsAdminDelayedContents', () => {
    dispatchRelatedEntrySearch();
  });

  /**
   * Lite Editor
   */
  DispatchLiteEditor();
  DispatchLiteEditorField(document);
  ACMS.addListener('acmsAdminDelayedContents', (e) => {
    const ctx = e.target || document;
    DispatchLiteEditorField(ctx);
  });
  ACMS.addListener('acmsAddCustomFieldGroup', (event) => {
    DispatchLiteEditorField(event.obj.item);
  });
  ACMS.addListener('acmsCustomFieldMakerPreview', (event) => {
    DispatchLiteEditorField(event.target);
  });
};
