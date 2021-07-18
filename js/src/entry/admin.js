import '../lib/polyfill';
import React from 'react';
import { render } from 'react-dom';
import DispatchSystemUpdate from '../admin/system-update';
import DispatchDatabaseExport from '../admin/database-export';
import DispatchStaticExport from '../admin/static-export';
import dispatchEntryEditor from '../admin/entry-editor';
import backgroundProcess from '../admin/background-process';

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
   * エントリー編集画面
   */
  dispatchEntryEditor();

  /**
   * カスタムフィールドメーカー
   */
  const fieldMaker = document.querySelector('#custom-field-maker');
  if (fieldMaker) {
    import(/* webpackChunkName: "custom-field-maker-css" */'custom-field-maker/src/css/custom-field-maker.css');
    import(/* webpackChunkName: "custom-field-maker" */'custom-field-maker').then(({ default: CustomFieldMaker }) => {
      render(<CustomFieldMaker />, fieldMaker);
    });
  }

  /**
   * OpenSereetMap
   */
  const dispatchGeoPicker = (item) => {
    import(/* webpackChunkName: "geo-picker" */'../lib/geo-picker').then(({ default: geoPicker }) => {
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
      const { default: PerfectScrollbar } = await import(/* webpackChunkName: "perfect-scrollbar" */'perfect-scrollbar');
      await import(/* webpackChunkName: "perfect-scrollbar-css" */'perfect-scrollbar/css/perfect-scrollbar.css');
      const psDom = document.querySelector('.js-scroll-contents');
      if (psDom) {
        const ps = new PerfectScrollbar(psDom, {
          wheelSpeed: 1,
          wheelPropagation: true,
          minScrollbarLength: 20
        });
        ps.update();
      }
    }
  })();

  /**
   * Color picker（IE10以上）
   */
  const dispatchColorPicer = () => {
    if (ACMS.Dispatch.Utility.browser().ltIE9) {
      const buttons = document.querySelectorAll('.js-acms-color-picker-submit');
      [].forEach.call(buttons, (button) => {
        button.parentNode.removeChild(button);
      });
    } else {
      const colorPickers = document.querySelector('.js-acms-color-picker');
      if (colorPickers) {
        import(/* webpackChunkName: "color-picker" */'../admin/color-picker').then(({ default: DispatchColorPicker }) => {
          DispatchColorPicker();
        });
      }
    }
  };
  ACMS.addListener('acmsAdminDelayedContents', () => {
    dispatchColorPicer();
  });
  dispatchColorPicer();


  /**
   * クイックサーチ（IE10以上）
   */
  const dispatchQuickSearch = () => {
    import(/* webpackChunkName: "quick-search" */'../components/quick-search').then(({ default: QuickSearch }) => {
      if (ACMS.Dispatch.Utility.browser().ltIE9 || ACMS.Config.auth === 'subscriber') {
        const buttons = document.querySelectorAll('.js-search-everything');
        [].forEach.call(buttons, (button) => {
          button.parentNode.removeChild(button);
        });
      } else if (ACMS.Config.quickSearchFeature === true && !/(\?|&)acms-preview-mode=/.test(location.href)) {
        document.body.insertAdjacentHTML('beforeend', "<div id='acms-search-everything'></div>");
        const quickSearch = document.querySelector('#acms-search-everything');
        render(<QuickSearch />, quickSearch);
      }
    });
  };
  const previewTarget = document.querySelector('.js-acms-preview');
  if (!previewTarget) {
    dispatchQuickSearch();
  }

  /**
   * プレビュー, タイムマシンモード
   */
  import(/* webpackChunkName: "preview" */'../lib/preview').then(({ default: preview }) => {
    preview();
  });

  /**
   * 擬似フォーム
   */
  const dispatchFakeForms = () => {
    const fakeForms = document.querySelectorAll('.js-fake-form');
    [].forEach.call(fakeForms, (item) => {
      const submit = item.querySelector('.js-submit');
      $(submit).unbind('click').bind('click', (e) => {
        e.preventDefault();

        const confirmMsg = $(submit).data('confirm');

        if (confirmMsg && !window.confirm(confirmMsg)) {
          return false;
        }
        const method = item.getAttribute('data-method');
        const form = document.createElement('form');

        form.setAttribute('method', method);
        form.style.display = 'none';

        $(item).find(':input, :radio, :checkbox, :submit').each((i, elm) => {
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
  ACMS.addListener('acmsAdminDelayedContents', () => {
    dispatchFakeForms();
  });
  dispatchFakeForms();


  /**
   * System update
   */
  DispatchSystemUpdate();

  /**
   * Database Export
   */
  DispatchDatabaseExport();

  /**
   * Static export
   */
  DispatchStaticExport();

  /**
   * CSVインポート
   */
  if (document.querySelector('#js-background-csv-import')) {
    const target = document.querySelector('#js-background-csv-import');
    const json = target.getAttribute('data-json');
    backgroundProcess('#js-background-csv-import', json, 1000);
  }

  /**
   * 固定ナビゲーション
   */
  const scrollAreaHeight = $('.js-scroll-fixed').height();
  $('.js-scroll-contents').css('padding-bottom', scrollAreaHeight);

  /**
   * Select2
   */
  const dispatchSelect2 = (elm) => {
    const target = elm || document;
    const selectBox = target.querySelectorAll(ACMS.Config.select2Mark);
    [].forEach.call(selectBox, (item) => {
      const option = {
        containerCssClass: 'acms-admin-selectbox',
        dropdownCssClass: 'acms-admin-select-dropdown',
      };
      if (item.querySelectorAll('option').length >= ACMS.Config.select2Threshold) {
        import(/* webpackChunkName: "select2" */'../lib/select2').then(({ default: select2 }) => {
          select2(item, option);
        });
      }
    });
  };
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
      localStorage.setItem(storageKey, Date.now() + (60 * 60 * 12 * 1000));
    }
  });

  /**
   * AdminMenu editor
   */
  const dispatchAdminMenuEditor = (elm) => {
    const adminMenuEditor = elm.querySelector(ACMS.Config.adminMenuEditMark);
    if (adminMenuEditor) {
      import(/* webpackChunkName: "admin-menu" */'../components/admin-menu').then(({ default: AdminMenuEdit }) => {
        const json = JSON.parse(document.querySelector('#admin-menu-json').innerHTML);
        json.cards = json.cards.filter((card => card.id));
        json.lanes = json.lanes.filter((lane => lane.id));
        const { lanes: laneArry } = json;
        const lanes = laneArry.map((lane) => {
          const cards = json.cards.filter(card => card.laneId === lane.id);
          return { ...lane, cards };
        });
        if (lanes[0]) {
          lanes[0].draggable = false;
        }
        render(<AdminMenuEdit data={{ lanes }} />, adminMenuEditor);
      });
    }
  };
  dispatchAdminMenuEditor(document);
  ACMS.addListener('acmsAdminDelayedContents', () => {
    dispatchAdminMenuEditor(document);
  });

  /**
   * Navigation editor
   */
  const dispatchNavigationEditor = (elm) => {
    const navigationEditor = elm.querySelector(ACMS.Config.navigationEditMark);
    if (navigationEditor && !/^ie/.test(ACMS.Dispatch.Utility.getBrowser())) {
      import(/* webpackChunkName: "navigation-edit" */'../components/navigation-edit').then(({ default: NavigationEdit }) => {
        $('.js-navigation-ie').remove();
        const json = document.querySelector('#js-navigation-json');
        const items = JSON.parse(json.innerHTML);

        import(/* webpackChunkName: "html-entities" */'html-entities').then(({ AllHtmlEntities: Entities }) => {
          const entities = new Entities();
          items.forEach((item) => {
            item.navigation_attr = entities.decode(item.navigation_attr);
            item.navigation_a_attr = entities.decode(item.navigation_a_attr);
            item.navigation_label = entities.decode(item.navigation_label);
          });
          render(
            <NavigationEdit
              items={items}
              message={ACMS.Config.navigationMessage
              }
            />,
            navigationEditor
          );
        });
      });
    } else {
      $('.js-navigation-ie').show();
    }
  };
  dispatchNavigationEditor(document);
  ACMS.addListener('acmsAdminDelayedContents', () => {
    dispatchNavigationEditor(document);
  });

  const dispatchMediaEditor = async (elm) => {
    try {
      const mediaEditor = elm.querySelector(ACMS.Config.mediaAdminMark);
      const { default: MediaEdit } = await import(/* webpackChunkName: "media-edit" */'../components/media-edit');
      if (mediaEditor) {
        render(<MediaEdit />, mediaEditor);
      }
    } catch (error) {
      console.log(error);
    }
  };
  dispatchMediaEditor(document);
  ACMS.addListener('acmsAdminDelayedContents', () => {
    dispatchMediaEditor(document);
  });
  /**
   * Banner Editor
   */
  const dispatchBannerEditor = async (elm) => {
    const bannerEditor = elm.querySelector(ACMS.Config.bannerEditMark);
    if (!bannerEditor) {
      return;
    }
    const { AllHtmlEntities: Entities } = await import(/* webpackChunkName: "html-entities" */'html-entities');
    const { default: BannerEdit } = await import(/* webpackChunkName: "banner-edit" */'../components/banner-edit');
    $('.js-banner-ie').remove();
    const ele = document.querySelector('#js-banner-json');
    const json = ele.innerHTML;
    const decoded = Entities.decode(json);
    const replaced = decoded.replace(/\\\//g, '/');
    const items = JSON.parse(replaced);
    const entities = new Entities();
    const attr1 = $('.js-label-attr1').val();
    const attr2 = $('.js-label-attr2').val();
    const hide1 = $('.js-hide-attr1').val();
    const hide2 = $('.js-hide-attr2').val();
    const tooltip1 = $('.js-tooltip-attr1').val();
    const tooltip2 = $('.js-tooltip-attr2').val();
    items.forEach((item) => {
      item.banner_attr = entities.decode(item.banner_attr);
      item.banner_a_attr = entities.decode(item.banner_a_attr);
      item.banner_label = entities.decode(item.banner_label);
    });
    render(
      <BannerEdit
        attr1={attr1}
        attr2={attr2}
        hide1={hide1}
        hide2={hide2}
        tooltip1={tooltip1}
        tooltip2={tooltip2}
        items={items}
        message={ACMS.Config.bannerMessage}
      />,
      bannerEditor
    );
  };
  dispatchBannerEditor(document);
  ACMS.addListener('acmsAdminDelayedContents', () => {
    dispatchBannerEditor(document);
  });

  const dispatchMediaField = async (dom) => {
    const { default: dispatch } = await import(/* webpackChunkName: "media-field" */'../admin/media-insert-field');
    dispatch(dom);
  };

  dispatchMediaField(document);

  ACMS.addListener('acmsAddUnit', async (event) => {
    const { default: dispatchMediaInsert } = await import(/* webpackChunkName: "media-insert" */'../admin/media-insert');
    dispatchMediaInsert(event.target);
    dispatchMediaField(event.target);
  });
  ACMS.addListener('acmsAddCustomFieldGroup', async (event) => {
    dispatchMediaField(event.target);
  });
  ACMS.addListener('acmsAdminDelayedContents', async (event) => {
    dispatchMediaField(event.target);
  });
  ACMS.addListener('acmsCustomFieldMakerPreview', async (event) => {
    dispatchMediaField(event.target);
  });

  /**
   * Dispatch acmsAdminReady
   */
  ACMS.dispatchEvent('acmsAdminReady');

  /**
   * GeoPicker
   */
  const dispatchGeoInput = () => {
    const $geoInput = $('.js-geo_input');
    const $geoForm = $('.js-geo_form');
    if ($geoInput.size() && $geoForm.size()) {
      const $lat = $geoForm.find('[name=geo_lat]');
      const $lng = $geoForm.find('[name=geo_lng]');
      const $zoom = $geoForm.find('[name=geo_zoom]');
      const $gmapTarget = $geoForm.find('.js-map_editable-container-flag');
      const defaultLat = ACMS.Config.adminLocationDefaultLat ? ACMS.Config.adminLocationDefaultLat : '';
      const defaultLng = ACMS.Config.adminLocationDefaultLng ? ACMS.Config.adminLocationDefaultLng : '';
      const defaultZoom = ACMS.Config.adminLocationDefaultZoom ? ACMS.Config.adminLocationDefaultZoom : '10';
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
        ACMS.dispatchEvent('onGeoInfoAdded', $geoForm.get(0));
      };
      const deleteGeo = function () {
        $geoForm.hide();
        $geoInput.val(ACMS.i18n('geo.message2'));
        $geoInput.data('type', 'delete');
        $lat.val('');
        $lng.val('');
        $zoom.val('');
      };

      $geoInput.bind('click', () => {
        const type = $geoInput.data('type');
        if (type === 'add') {
          deleteGeo();
        } else if (type === 'delete') {
          addGeo();
        }
      }).trigger('click');
    }
  };
  dispatchGeoInput();

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
      console.log('JSONのparseに失敗しました。');
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

