import './lib/polyfill';

import dispatchSystemUpdate from './features/system-update';
import dispatchBackup from './features/backup';
import dispatchStaticExport from './features/static-export';
import dispatchCsvImport from './features/csv-import';
import dispatchWebhookEventSelect from './dispatch/dispatch-webhook-event-select';
import dispatchPreviewMode from './dispatch/dispatch-preview-mode';
import dispatchTimeMachineMode from './dispatch/dispatch-timemachine-mode';
import dispatchInlinePreview from './dispatch/dispatch-inline-preview';
import dispatchAdminMenuEditor from './dispatch/dispatch-admin-menu-editor';
import dispatchBannerEditor from './dispatch/dispatch-banner-editor';
import dispatchColorPicker from './dispatch/dispatch-color-picker';
import dispatchSelect2 from './dispatch/dispatch-select2';
import dispatchCustomFieldMaker from './dispatch/dispatch-custom-field-maker';
import dispatchAuditLogDetailModal from './dispatch/dispatch-audit-log-detail-modal';
import dispatchQuickSearch from './dispatch/dispatch-quick-search';
import dispatchEntryLockModal from './dispatch/dispatch-entry-lock-modal';
import dispatchNavigationEditor from './dispatch/dispatch-navigation-editor';
import { dispatchMediaAdmin, dispatchMediaField, dispatchMediaUnit } from './dispatch/media';
import dispatchCategorySelect from './dispatch/dispatch-category-select';
import dispatchTagSelect from './dispatch/dispatch-tag-select';
import dispatchSubCategorySelect from './dispatch/dispatch-sub-category-select';
import dispatchAtable from './dispatch/dispatch-a-table';
import dispatchRichEditor from './dispatch/dispatch-rich-editor';
import dispatchRelatedEntry from './dispatch/dispatch-related-entry';
import { dispatchLiteEditor, dispatchLiteEditorField } from './dispatch/lite-editor';

ACMS.Ready(() => {
  __webpack_public_path__ = ACMS.Config.root; // eslint-disable-line

  /**
   * Delayed contents event
   */
  ACMS.addListener('acmsAdminSelectTab', (e) => {
    ACMS.dispatchEvent('acmsAdminDelayedContents', e.target, e.obj);
  });
  ACMS.addListener('acmsAdminShowTabPanel', (e) => {
    ACMS.dispatchEvent('acmsAdminDelayedContents', e.target, e.obj);
  });
  ACMS.addListener('acmsDialogOpened', (e) => {
    ACMS.dispatchEvent('acmsAdminDelayedContents', e.target, e.obj);
  });
  ACMS.addListener('acmsAddCustomFieldGroup', (e) => {
    ACMS.dispatchEvent('acmsAdminDelayedContents', e.target, e.obj);
  });

  /**
   * モーダルないのいらない情報を削除
   */
  ACMS.addListener('acmsDialogOpened', (e) => {
    $('.js-hide-on-modal', e.target).remove();
  });

  /**
   * カテゴリー選択
   */
  dispatchCategorySelect(document);
  ACMS.addListener('acmsAdminDelayedContents', (event) => {
    const context = event.obj.item || event.target;
    dispatchCategorySelect(context);
  });

  /**
   * タグ選択
   */
  dispatchTagSelect(document);
  ACMS.addListener('acmsAdminDelayedContents', (event) => {
    const context = event.obj.item || event.target;
    dispatchTagSelect(context);
  });

  /**
   * サブカテゴリー選択
   */
  dispatchSubCategorySelect(document);
  ACMS.addListener('acmsAdminDelayedContents', (event) => {
    const context = event.obj.item || event.target;
    dispatchSubCategorySelect(context);
  });

  /**
   * 会員限定記事チェックボックス
   */
  const dispatchMembersOnlyCheckbox = () => {
    const checkboxs = document.querySelectorAll('.js-input-members-only');
    const normalDelimiter = document.querySelector('.js-normal-delimiter');
    const membersOnlyDelimiter = document.querySelector('.js-members-only-delimiter');

    if (normalDelimiter && membersOnlyDelimiter && checkboxs && checkboxs.length > 0) {
      [].forEach.call(checkboxs, (input) => {
        input.addEventListener('change', () => {
          if (input.checked) {
            normalDelimiter.style.display = 'none';
            membersOnlyDelimiter.style.display = 'block';
          } else {
            normalDelimiter.style.display = 'block';
            membersOnlyDelimiter.style.display = 'none';
          }
        });
      });
    }
  };
  dispatchMembersOnlyCheckbox();

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
  function dispatchATableUnit(context = document) {
    const tables = context.querySelectorAll('.js-table-unit');
    [].forEach.call(tables, (table) => {
      dispatchAtable(table);
    });
  }
  dispatchATableUnit(document);
  ACMS.addListener('acmsAddUnit', (event) => {
    if (event.obj.item && event.obj.item.querySelector) {
      dispatchATableUnit(event.obj.item);
      dispatchATableField(event.obj.item);
    }
  });
  ACMS.addListener('acmsCustomFieldMakerPreview', (event) => {
    dispatchATableField(event.target);
  });

  /**
   * Rich Editor
   */
  dispatchRichEditor(document);
  ACMS.addListener('acmsAddUnit', (event) => {
    dispatchRichEditor(event.obj.item);
  });
  ACMS.addListener('acmsAdminDelayedContents', (e) => {
    const context = e.obj.item || e.target;
    dispatchRichEditor(context);
  });
  ACMS.addListener('acmsCustomFieldMakerPreview', (event) => {
    dispatchRichEditor(event.target);
  });

  /**
   * 関連エントリー
   */
  dispatchRelatedEntry(document);
  ACMS.addListener('acmsAdminDelayedContents', (event) => {
    const context = event.obj.item || event.target;
    dispatchRelatedEntry(context);
  });

  /**
   * Lite Editor
   */
  dispatchLiteEditor();
  dispatchLiteEditorField(document);
  ACMS.addListener('acmsAddUnit', (event) => {
    dispatchLiteEditorField(event.obj.item);
  });
  ACMS.addListener('acmsAdminDelayedContents', (e) => {
    const ctx = e.target || document;
    dispatchLiteEditorField(ctx);
  });
  ACMS.addListener('acmsAddCustomFieldGroup', (event) => {
    const { item } = event.obj;
    dispatchLiteEditorField(item);
  });
  ACMS.addListener('acmsCustomFieldMakerPreview', (event) => {
    dispatchLiteEditorField(event.target);
  });

  /**
   * カスタムフィールドメーカー
   */
  dispatchCustomFieldMaker(document);

  /**
   * OpenSereetMap
   */
  const dispatchGeoPicker = (item) => {
    import(/* webpackChunkName: "geo-picker" */ './lib/geo-picker').then(({ default: geoPicker }) => {
      geoPicker(item);
    });
  };
  const openStreetMapsPickers = document.querySelectorAll('.js-open-street-map-editable');
  [].forEach.call(openStreetMapsPickers, (item) => {
    dispatchGeoPicker(item);
  });
  ACMS.addListener('acmsAddCustomFieldGroup', (e) => {
    dispatchGeoPicker(e.obj.item);
  });
  ACMS.addListener('acmsAddUnit', (e) => {
    dispatchGeoPicker(e.obj.item);
  });
  ACMS.addListener('onGeoInfoAdded', (e) => {
    dispatchGeoPicker(e.target);
  });

  /**
   * メニューのスクロールバー（IE以外）
   */
  (async () => {
    if (!/^ie/.test(ACMS.Dispatch.Utility.getBrowser())) {
      const { default: PerfectScrollbar } = await import(
        /* webpackChunkName: "perfect-scrollbar" */ 'perfect-scrollbar'
      );
      await import(/* webpackChunkName: "perfect-scrollbar-css" */ 'perfect-scrollbar/css/perfect-scrollbar.css');
      const psDom = document.querySelector('.js-scroll-contents');
      if (psDom) {
        const ps = new PerfectScrollbar(psDom, {
          wheelSpeed: 1,
          wheelPropagation: true,
          minScrollbarLength: 20,
        });
        ps.update();
      }
    }
  })();

  /**
   * API管理画面のAPI KEY生成
   */
  (() => {
    const target = document.querySelector('.js-x-api-key');
    const generateUuid = () => {
      // https://github.com/GoogleChrome/chrome-platform-analytics/blob/master/src/internal/identifier.js
      // const FORMAT: string = "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx";
      const chars = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.split('');
      for (let i = 0, len = chars.length; i < len; i++) {
        // eslint-disable-next-line default-case
        switch (chars[i]) {
          case 'x':
            chars[i] = Math.floor(Math.random() * 16).toString(16);
            break;
          case 'y':
            chars[i] = (Math.floor(Math.random() * 4) + 8).toString(16);
            break;
        }
      }
      return chars.join('');
    };
    if (target) {
      const input = target.querySelector('.js-key-input');
      const updateButton = target.querySelector('.js-update-key');
      if (input && !input.value) {
        input.value = generateUuid();
      }
      if (updateButton) {
        updateButton.addEventListener('click', (e) => {
          e.preventDefault();
          if (window.confirm('本当にキーを更新してもよろしいですか？保存するまでは変更されません。')) {
            input.value = generateUuid();
          }
        });
      }
    }
  })();

  /**
   * 監査ログページ
   */
  dispatchAuditLogDetailModal(document);

  /**
   * Webhook 管理ページ
   */
  dispatchWebhookEventSelect(document);

  /**
   * Color picker
   */
  dispatchColorPicker(document);
  ACMS.addListener('acmsAdminDelayedContents', (event) => {
    const context = event.obj.item || event.target;
    dispatchColorPicker(context);
  });

  /**
   * クイックサーチ
   */
  dispatchQuickSearch(document);

  /**
   * エントリーロック
   */
  if (ACMS.Config.edition === 'professional' || ACMS.Config.edition === 'enterprise') {
    dispatchEntryLockModal(document);
  }

  /**
   * プレビューモード（インライン）
   */
  dispatchInlinePreview(document);

  /**
   * プレビューモード（モーダル）
   */
  dispatchPreviewMode(document);

  /**
   * タイムマシーンモード
   */
  dispatchTimeMachineMode(document);

  /**
   * 擬似フォーム
   */
  const dispatchFakeForms = (context = document) => {
    const fakeForms = context.querySelectorAll('.js-fake-form');
    [].forEach.call(fakeForms, (item) => {
      const submit = item.querySelector('.js-submit');
      $(submit)
        .unbind('click')
        .bind('click', (e) => {
          e.preventDefault();

          const confirmMsg = $(submit).data('confirm')?.replace(/\\n/g, '\n');

          if (confirmMsg && !window.confirm(confirmMsg)) {
            return false;
          }
          const method = item.getAttribute('data-method');
          const form = document.createElement('form');

          form.setAttribute('method', method);
          form.style.display = 'none';

          $(item)
            .find(':input, :radio, :checkbox, :submit')
            .each((i, elm) => {
              const copy = elm.cloneNode(true);
              copy.value = elm.value;
              form.appendChild(copy);
            });

          const csrfToken = document.createElement('input');
          csrfToken.type = 'hidden';
          csrfToken.name = 'formToken';
          csrfToken.value = window.csrfToken;
          form.appendChild(csrfToken);
          document.body.appendChild(form);
          form.submit();
        });
    });
    $('form').submit(function () {
      $(this).find('.js-fake-form').remove();
    });
  };
  ACMS.addListener('acmsAdminDelayedContents', (event) => {
    const context = event.obj.item || event.target;
    dispatchFakeForms(context);
  });
  dispatchFakeForms(document);

  /**
   * System update
   */
  dispatchSystemUpdate(document);

  /**
   * Backup
   */
  dispatchBackup(document);

  /**
   * Static export
   */
  dispatchStaticExport(document);

  /**
   * CSV Import
   */
  dispatchCsvImport(document);

  /**
   * 固定ナビゲーション
   */
  const scrollAreaHeight = $('.js-scroll-fixed').height();
  $('.js-scroll-contents').css('padding-bottom', scrollAreaHeight);

  /**
   * Select2
   */
  dispatchSelect2(document);
  ACMS.addListener('acmsAdminDelayedContents', (e) => {
    dispatchSelect2(e.target);
  });
  ACMS.addListener('acmsAddUnit', (e) => {
    dispatchSelect2(e.obj.item);
  });
  ACMS.addListener('acmsAddCustomFieldGroup', (e) => {
    dispatchSelect2(e.obj.item);
  });

  /**
   * Disclose password
   */
  const disclosePassword = document.querySelectorAll('.js-disclose_password');
  [].forEach.call(disclosePassword, (item) => {
    item.addEventListener('change', (event) => {
      const selector = event.target.getAttribute('data-target');
      const target = document.querySelector(selector);
      if (event.target.checked) {
        target.setAttribute('type', 'text');
      } else {
        target.setAttribute('type', 'password');
      }
    });
  });

  /**
   * ライセンス警告
   */
  const toastToggle = document.querySelectorAll('.js-admin-toast-toggle');
  [].forEach.call(toastToggle, (item) => {
    const closeClass = 'acms-admin-toast-closed';

    item.addEventListener('mouseenter', (event) => {
      item.classList.remove(closeClass);
      event.preventDefault();
    });
    item.addEventListener('mouseleave', (event) => {
      item.classList.add(closeClass);
      event.preventDefault();
    });

    const storageKey = 'acms-license-alert';
    const time = localStorage.getItem(storageKey);
    if (!time || Date.now() > parseInt(time, 10)) {
      item.classList.remove(closeClass);
      setTimeout(() => {
        item.classList.add(closeClass);
      }, 5000);
      localStorage.setItem(storageKey, Date.now() + 60 * 60 * 12 * 1000);
    }
  });

  /**
   * AdminMenu editor
   */
  dispatchAdminMenuEditor(document);
  ACMS.addListener('acmsAdminDelayedContents', () => {
    dispatchAdminMenuEditor(document);
  });

  /**
   * Navigation editor
   */
  dispatchNavigationEditor(document);
  ACMS.addListener('acmsAdminDelayedContents', () => {
    dispatchNavigationEditor(document);
  });

  /**
   * Banner Editor
   */
  dispatchBannerEditor(document);
  ACMS.addListener('acmsAdminDelayedContents', (event) => {
    const context = event.obj.item || event.target;
    dispatchBannerEditor(context);
  });

  /**
   * メディア管理
   */
  dispatchMediaAdmin(document);
  ACMS.addListener('acmsAdminDelayedContents', (event) => {
    const context = event.obj.item || event.target;
    dispatchMediaAdmin(context);
  });

  dispatchMediaField(document);

  ACMS.addListener('acmsAddUnit', (event) => {
    dispatchMediaUnit(event.target);
    dispatchMediaField(event.target);
  });
  ACMS.addListener('acmsDialogOpened', (event) => {
    dispatchMediaField(event.target);
  });
  ACMS.addListener('acmsAddCustomFieldGroup', (event) => {
    dispatchMediaField(event.target);
  });

  ACMS.addListener('acmsCustomFieldMakerPreview', (event) => {
    dispatchMediaField(event.target);
  });

  /**
   * Dispatch acmsAdminReady
   */
  ACMS.dispatchEvent('acmsAdminReady');

  /**
   * GeoPicker
   */
  const dispatchGeoInput = (context) => {
    const $geoInput = $('.js-geo_input', context);
    const $geoForm = $('.js-geo_form', context);
    if (!$geoInput.length || !$geoForm.length) {
      return;
    }
    const $lat = $geoForm.find('[name=geo_lat]');
    const $lng = $geoForm.find('[name=geo_lng]');
    const $zoom = $geoForm.find('[name=geo_zoom]');
    const $gmapTarget = $geoForm.find('.js-map_editable-container-flag');
    const defaultLat = ACMS.Config.adminLocationDefaultLat || '';
    const defaultLng = ACMS.Config.adminLocationDefaultLng || '';
    const defaultZoom = ACMS.Config.adminLocationDefaultZoom || '10';

    const addGeo = function () {
      $geoForm.show();
      $geoInput.val(ACMS.i18n('geo.message1'));
      $geoInput.data('type', 'add');
      if (!$lat.val()) {
        $lat.val(defaultLat);
      }
      if (!$lng.val()) {
        $lng.val(defaultLng);
      }
      if (!$zoom.val()) {
        $zoom.val(defaultZoom);
      }
      if ($gmapTarget) {
        $gmapTarget.addClass('js-map_editable-container');
        ACMS.Dispatch.Admin($geoForm.parent().get(0));
      }
      ACMS.dispatchEvent('onGeoInfoAdded', $geoForm.parent().get(0));
    };
    const deleteGeo = function () {
      $geoForm.hide();
      $geoInput.val(ACMS.i18n('geo.message2'));
      $geoInput.data('type', 'delete');
      $lat.val('');
      $lng.val('');
      $zoom.val('');
    };

    $geoInput
      .on('click', () => {
        const type = $geoInput.data('type');
        if (type === 'add') {
          deleteGeo();
        } else if (type === 'delete') {
          addGeo();
        }
      })
      .trigger('click');
  };
  dispatchGeoInput(document);
  ACMS.addListener('acmsDialogOpened', (event) => {
    dispatchGeoInput(event.obj.item);
  });

  /**
   * 編集画面のラベル変更
   */
  const changeEntryLabels = () => {
    const EditorJson = document.getElementById('entry-labels');
    if (!EditorJson) {
      return;
    }
    try {
      const json = JSON.parse(EditorJson.innerHTML);
      Object.keys(json).forEach((item) => {
        const target = document.getElementById(item);
        if (target && json[item]) {
          target.innerHTML = json[item];
        }
      });
    } catch (e) {
      console.log('JSONのparseに失敗しました。'); // eslint-disable-line no-console
    }
    const table = document.getElementById('js-entry-detail-table');
    if (!table) {
      return;
    }
    let flag = false;
    const trs = table.querySelectorAll('tbody > tr');
    [].forEach.call(trs, (tr) => {
      const style = window.getComputedStyle(tr);
      if (style && style.display !== 'none') {
        flag = true;
      }
    });
    if (!flag) {
      table.style.display = 'none';
    }
  };

  changeEntryLabels();
  ACMS.addListener('acmsAdminDelayedContents', changeEntryLabels);

  /**
   * スタイルガイド
   */
  if ($('.js-navSubOpener').length > 0) {
    $('.js-navSubOpener').click(() => {
      $('.navSubGroup').toggleClass('acms-admin-block');
      return false;
    });

    const calcHeight = () => {
      const adminNav = $('.acms-navbar-admin').height() + 40;
      const height = $(window).height() - adminNav;
      $('.navSubGroup').height(`${height}px`);
    };

    calcHeight();

    $(window).resize(calcHeight);

    $('.js-sample-start').click(function () {
      $(this).parent().parent().toggleClass('active');
    });
  }
});
