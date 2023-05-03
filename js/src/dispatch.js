import DocumentOutliner from 'document-outliner';
import { FocusedImage } from 'image-focus';
import ScrollHint from 'scroll-hint';
import lazyLoad from './lib/lazy-load';
import tooltip from './lib/tooltip';
import ResizeImage from './lib/resize-image/resize-image';
import { findAncestor, addClass, removeClass } from './lib/dom';
import { contrastColor, rgb2hex } from './lib/utility';

export default (context) => {
  //--------
  // resize
  $(ACMS.Config.resizeImageTargetMarkCF, context).each(function () {
    if (!this.closest('.item-template')) {
      const resizeImg = new ResizeImage(this);
      resizeImg.resize();
    }
  });

  //-------------
  // scroll hint
  const TableUnitScrollHint = '.js-table-unit-scroll-hint';
  if (document.querySelector(ACMS.Config.scrollHintMark) || document.querySelector(TableUnitScrollHint)) {
    import(/* webpackChunkName: "scroll-hint-css" */'scroll-hint/css/scroll-hint.css').then(() => {
      // build in js
      new ScrollHint(ACMS.Config.scrollHintMark, ACMS.Config.scrollHintConfig); // eslint-disable-line no-new

      // table unit
      new ScrollHint(TableUnitScrollHint, { ...ACMS.Config.scrollHintConfig, applyToParents: true }); // eslint-disable-line no-new
    });
  }

  //------------
  // lazy load
  ACMS.Library.LazyLoad(ACMS.Config.lazyLoadMark, ACMS.Config.lazyLoadConfig);

  //---------
  // in-view
  lazyLoad(
    ACMS.Config.lazyContentsMark,
    () => true,
    (item) => {
      const type = item.getAttribute('data-type');
      if (!type) {
        return;
      }
      const script = document.createElement(type);
      item.attributes.forEach((data) => {
        const matches = data.name.match(/^data-(.*)/);
        if (matches && matches[1] !== 'type') {
          script[matches[1]] = data.value;
        }
      });
      item.appendChild(script);
    },
  );

  //--------------
  // focus image
  [].forEach.call(document.querySelectorAll('.js-focused-image'), (image) => {
    image.style.visibility = 'visible';
    new FocusedImage(image); // eslint-disable-line no-new
  });

  //-------------
  // pdf viewer
  const { pdfPreviewConfig } = ACMS.Config;
  lazyLoad(pdfPreviewConfig.mark, (wrapper) => {
    const elm = wrapper.querySelector(pdfPreviewConfig.previewMark);
    if (elm) {
      return elm.getAttribute(pdfPreviewConfig.lazyAttr) === '1'; // lazy-load 判定
    }
    return false;
  }, (wrapper) => {
    const elm = wrapper.querySelector(pdfPreviewConfig.previewMark);
    if (!elm) {
      return;
    }
    const url = elm.getAttribute(pdfPreviewConfig.pdfAttr);
    if (!url) {
      return;
    }
    const page = parseInt(elm.getAttribute(pdfPreviewConfig.pageAttr), 10) || 1;
    const imageWidth = parseInt(elm.getAttribute(pdfPreviewConfig.widthAttr), 10) || elm.clientWidth;

    import(/* webpackChunkName: "pdf2image" */'./lib/pdf2image').then(async ({ default: Pdf2Image }) => {
      const pdf2Image = new Pdf2Image(url);
      const prevButton = wrapper.querySelector(pdfPreviewConfig.prevBtnMark);
      const nextButton = wrapper.querySelector(pdfPreviewConfig.nextBtnMark);
      const showClass = pdfPreviewConfig.showBtnClass;
      const image = await pdf2Image.getPageImage(page, imageWidth);
      if (image) {
        elm.src = image;
      }
      const checkButton = async () => {
        if (prevButton) {
          if (await pdf2Image.hasPrevPage()) {
            addClass(prevButton, showClass);
          } else {
            removeClass(prevButton, showClass);
          }
        }
        if (nextButton) {
          if (await pdf2Image.hasNextPage()) {
            addClass(nextButton, showClass);
          } else {
            removeClass(nextButton, showClass);
          }
        }
      };
      checkButton();
      if (prevButton) {
        prevButton.addEventListener('click', async (e) => {
          e.preventDefault();
          const prevImage = await pdf2Image.getPrevImage(imageWidth);
          if (prevImage) {
            elm.src = prevImage;
          }
          checkButton();
        });
      }
      if (nextButton) {
        nextButton.addEventListener('click', async (e) => {
          e.preventDefault();
          const nextImage = await pdf2Image.getNextImage(imageWidth);
          if (nextImage) {
            elm.src = nextImage;
          }
          checkButton();
        });
      }
    });
  });

  //---------------
  // OpenStreetMap
  lazyLoad(
    ACMS.Config.openStreetMapMark,
    (elm) => elm.getAttribute('data-lazy') === 'true',
    (item) => {
      import(/* webpackChunkName: "open-street-map" */'./lib/open-street-map').then(({ default: openStreetMap }) => {
        openStreetMap(item);
      });
    },
  );

  //-------------
  // Google Maps
  lazyLoad(
    ACMS.Config.s2dReadyMark,
    (elm) => elm.getAttribute('data-lazy') === 'true',
    (item) => {
      ACMS.Library.googleLoadProxy('maps', '3', {
        callback: () => {
          ACMS.Dispatch._static2dynamic(item);
        },
        options: {
          region: ACMS.Config.s2dRegion,
        },
      });
    },
  );
  lazyLoad(
    ACMS.Config.s2dMark,
    (elm) => elm.getAttribute('data-lazy') === 'true',
    (item) => {
      ACMS.Dispatch.static2dynamic(item);
    },
  );

  //---------------
  // StreetView
  lazyLoad(
    ACMS.Config.streetViewMark,
    (elm) => elm.getAttribute('data-lazy') === 'true',
    (item) => {
      import(/* webpackChunkName: "open-street-map" */'./lib/street-view').then(({ default: streetView }) => {
        streetView(item, ACMS.Config.googleApiKey);
      });
    },
  );

  //---------
  // tooltip
  $('.js-acms-tooltip', context).click(function () {
    tooltip(this);
    return false;
  });

  const tooltips = document.querySelectorAll('.js-acms-tooltip-hover');

  [].forEach.call(tooltips, (item) => {
    let interval;

    if (findAncestor(item, '#js-edit_inplace-box')) {
      return;
    }

    item.addEventListener('mouseenter', () => {
      tooltip(item, true);
      interval = setInterval(() => {
        if (!document.body.contains(item)) {
          $('.js-tooltip').remove();
          clearInterval(interval);
        }
      }, 300);
    });

    item.addEventListener('mouseleave', (e) => {
      clearInterval(interval);
      if ($(e.relatedTarget).hasClass('js-tooltip')) {
        const leaveFunc = (evt) => {
          if (evt.relatedTarget !== item) {
            evt.relatedTarget.removeEventListener('mouseleave', leaveFunc);
            tooltip(item, false);
          }
        };
        e.relatedTarget.addEventListener('mouseleave', leaveFunc);
      } else {
        tooltip(item, false);
      }
    });
  });

  //---------
  // preview
  ACMS.Dispatch.Preview = () => {
    import(/* webpackChunkName: "preview" */'./lib/preview').then(({ default: preview }) => {
      preview();
    });
  };
  if (window.parent !== window && location.href) {
    window.parent.postMessage({ task: 'preview', url: location.href }, '*');
  }

  $(ACMS.Config.externalFormSubmitButton).each((index, button) => {
    $(button).click((e) => {
      e.preventDefault();
      const target = $(button).data('target');
      if (!target) {
        return;
      }
      const name = $(button).attr('name');
      if (name && name.match(/^ACMS_POST/)) {
        $(target).append(`<input type="hidden" name="${name}" value="true" />`);
      }
      $(target).submit();
    });
  });

  $(ACMS.Config.blankSubmitBtn).each((index, button) => {
    const form = findAncestor(button, 'form');
    $(button).click(() => {
      $(form).attr('target', '_blank');
    });
    $(form).submit(() => {
      setTimeout(() => {
        $(form).removeAttr('target');
      }, 100);
    });
  });

  const dispatchFlatPicker = async (ctx) => {
    //-------------------
    // flatpicker
    const flatPickerTarget = ctx.querySelectorAll(ACMS.Config.flatDatePicker);
    if (flatPickerTarget && flatPickerTarget.length) {
      const { default: flatPicker } = await import(/* webpackChunkName: "flatpickr" */'flatpickr');
      import(/* webpackChunkName: "flatpickr-css" */'flatpickr/dist/flatpickr.min.css');
      const options = {
        ...ACMS.Config.flatDatePickerConfig,
      };
      if (/^ja/.test(ACMS.i18n.lng)) {
        const lang = await import(/* webpackChunkName: "flatpickr-ja" */'flatpickr/dist/l10n/ja');
        options.locale = lang.Japanese;
      }
      [].forEach.call(flatPickerTarget, (item) => {
        if ($(item).hasClass('done')) {
          return;
        }
        options.defaultDate = item.value;
        const picker = flatPicker(item, options);
        item.setAttribute('autocomplete', 'off');
        item.addEventListener('change', (e) => {
          picker.jumpToDate(e.target.value);
          picker.setDate(e.target.value);
        });
        $(item).addClass('done');
      });
    }

    //-------------------
    // timepicker
    const flatTimePickerTarget = document.querySelectorAll(ACMS.Config.flatTimePicker);
    if (flatTimePickerTarget && flatTimePickerTarget.length) {
      const { default: flatPicker } = await import(/* webpackChunkName: "flatpickr" */'flatpickr');
      await import(/* webpackChunkName: "flatpickr-css" */'flatpickr/dist/flatpickr.min.css');
      [].forEach.call(flatTimePickerTarget, (item) => {
        if ($(item).hasClass('done')) {
          return;
        }
        const picker = flatPicker(item, {
          ...ACMS.Config.flatTimePickerConfig,
          defaultDate: item.value,
        });
        item.setAttribute('autocomplete', 'off');
        item.addEventListener('change', (e) => {
          picker.jumpToDate(e.target.value);
          picker.setDate(e.target.value);
        });
        $(item).addClass('done');
      });
    }
  };

  dispatchFlatPicker(document);
  ACMS.addListener('acmsAddCustomFieldGroup', (e) => {
    dispatchFlatPicker(e.target);
  });
  ACMS.addListener('acmsAddUnit', (e) => {
    dispatchFlatPicker(e.target);
  });

  //-------------------
  // contrast color
  const contrastColorTarget = document.querySelectorAll(ACMS.Config.contrastColorTarget);
  if (contrastColorTarget && contrastColorTarget.length) {
    [].forEach.call(contrastColorTarget, (item) => {
      const black = item.getAttribute('data-black-color') || '#000000';
      const white = item.getAttribute('data-white-color') || '#ffffff';
      let bgColor = item.getAttribute('data-bg-color');
      if (!bgColor) {
        const style = window.getComputedStyle(item);
        if (style) {
          bgColor = rgb2hex(style.backgroundColor);
        }
      }
      if (bgColor) {
        item.style.color = contrastColor(bgColor, black, white);
      }
    });
  }

  /**
   * Password strength checker
   */
  const passwordStrength = document.querySelectorAll(ACMS.Config.passwordStrengthMark);
  if (passwordStrength.length > 0) {
    import(/* webpackChunkName: "zxcvbn" */'./lib/zxcvbn').then(({ default: zxcvbn }) => {
      [].forEach.call(passwordStrength, (item) => {
        zxcvbn(item);
      });
    });
  }

  //-------------------
  // twitter login
  const twitterLogin = document.querySelector('.js-twitter-login');
  if (twitterLogin) {
    (async () => {
      const { default: login } = await import(/* webpackChunkName: "twitter-login" */'./lib/twitter-login');
      const { type } = twitterLogin.dataset;
      login(twitterLogin, type);
    })();
  }

  //-------------------
  // document-outliner
  const outlineTarget = document.querySelectorAll(ACMS.Config.documentOutlinerMark);
  if (outlineTarget && outlineTarget.length) {
    [].forEach.call(outlineTarget, (item) => {
      requestAnimationFrame(() => {
        const target = item.getAttribute('data-target');
        if (!target || !document.querySelector(target)) {
          return;
        }
        const outline = new DocumentOutliner(item);
        const overrideConfig = {};
        Object.keys(ACMS.Config.documentOutlinerConfig).forEach((key) => {
          let value = item.getAttribute(`data-${key}`);
          if (value) {
            if (isNaN(value) === false) {
              value = parseInt(value, 10);
            }
            if (value === 'true' || value === 'false') {
              value = value === 'true';
            }
            overrideConfig[key] = value;
          }
        });
        const config = { ...ACMS.Config.documentOutlinerConfig, ...overrideConfig };

        outline.makeList(target, config);
        [].forEach.call(document.querySelectorAll(ACMS.Config.scrollToMark), (anchor) => {
          ACMS.Dispatch.scrollto(anchor);
        });
      });
    });
  }
};
