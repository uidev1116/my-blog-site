import { isOldIE, getBrowser, random } from './lib/utility';

export default () => {
  //------------------
  // Dispatch.Utility
  ACMS.Dispatch.Utility = function (context) {
    const Config = ACMS.Config;

    //-----------
    // fix align
    if (Config.unitFixAlign !== 'off') {
      $('div[class^=column-image], div[class^=column-file], div[class^=column-eximage],  div[class^=column-media]', context).each(function () {
        const $img = $('img', this);
        let width = $img.width();
        const offset = $img.outerWidth() - width;
        const style = $(this).attr('style');

        if (ACMS.Dispatch.Utility.browser().ltIE9) {
          width = parseInt($img.attr('width'), 10);
        }

        if (width && style === undefined && !$(this).hasClass('js_notStyle')) {
          $(this).width(width + offset);
        }
        if (!$(this).next('.caption').size() && !$(this).find('.caption').size()) $(this).addClass('nocaption');
      });
      $('[class^=column-youtube-], [class^=column-video-]', context).each(function () {
        const $video = $('iframe', this);
        const width = $video.attr('width');

        const style = $(this).attr('style');
        if (width && style === undefined && !$(this).hasClass('js_notStyle')) {
          $(this).width(width);
        }
      });
      $('[class^=column-map], [class^=column-yolp]', context).each(() => {
        // let region = '';
        // if (ACMS.Config.s2dRegion) {
        //   region = `&region=${ACMS.Config.s2dRegion}`;
        // }

        ACMS.Library.googleLoadProxy('maps', '3', {
          callback() {
            const style = $(this).attr('style');
            if (style === undefined && !$(this).hasClass('js_notStyle')) {
              $(this).width(($(':first-child', this).width()));
            }
          },
          options: {
            region: ACMS.Config.s2dRegion
          }
        });
      });

      if (ACMS.Dispatch.Utility.browser_ie6() && !$.boxModel) {
        $('.column-image-center, .column-file-center, .column-youtube-center, .column-eximage-center, .column-media-center', context).css('width', '100%');
        $('.column-map-center', context).wrap('<div style="text-align:center; width:100%"></div>');
      }
    }

    //------------------------------------------
    // unitgroup to make all of uniform height
    if (Config.unitGroupAlign) {
      let timer;

      $(window).resize(() => {
        const $unitGroup = $(Config.unitGroupAlignMark);
        const containerWidth = $unitGroup.parent().width();
        let currentWidth = 0;
        let count = 0;

        clearTimeout(timer);
        timer = setTimeout(() => {
          _.each($unitGroup, (v) => {
            const $unit = $(v);
            const unitW = $unit.outerWidth(true) - 1;

            $unit.css({
              clear: 'none'
            });
            if (!$unit.prev().hasClass(Config.unitGroupAlignMark.substring(1))) {
              currentWidth = 0;
              count = 0;
            }
            if (1
              && count > 0
              && ((containerWidth - (currentWidth + unitW)) < -1)
            ) {
              $unit.css({
                clear: 'both'
              });
              currentWidth = unitW;
              count = 1;
            } else {
              currentWidth += unitW;
              count++;
            }
          });
        }, Config.unitGroupAlignInterval);
      }).trigger('resize');
    }

    //------------
    // highlight
    if (Config.keyword && !Config.admin) {
      let searchKeywordTag = Config.searchKeywordMatchTag;
      if (!searchKeywordTag) {
        searchKeywordTag = 'mark';
      }
      $.each(Config.keyword.split(' '), function (j) {
        const word = this;
        $(Config.searchKeywordHighlightMark, context).find('*').addBack().contents()
          .filter(function () { // eslint-disable-line array-callback-return
            if (this.nodeType === 3) {
              const elm = this;
              let text = elm.nodeValue;
              text = text.replace(new RegExp(`(${word})`, 'ig'), `<${searchKeywordTag} class="${Config.searchKeywordMatchClass}${parseInt(j, 10) + 1}">$1</${searchKeywordTag}>`);
              $(elm).before($.parseHTML(text));
              $(elm).remove();
            }
          });
      });
    }

    //--------
    // toggle
    $(`[class*=${Config.toggleHeadClassSuffix}]`, context).css('cursor', 'pointer').click(function () {
      if (!(new RegExp(`([^\\s]*)${Config.toggleHeadClassSuffix}`)).test(this.className)) return false;
      const mark = RegExp.$1;
      const $target = $(`.${mark}${Config.toggleBodyClassSuffix}`);
      if (!$target.size()) return false;
      $target.slideToggle();
      return false;
    });
    $(`[class*="${Config.toggleBodyClassSuffix}"]`, context).hide();

    //------
    // fade
    $(`[class*=${Config.fadeHeadClassSuffix}]`, context).css('cursor', 'pointer').click(function () {
      if (!(new RegExp(`([^\\s]*)${Config.fadeHeadClassSuffix}`)).test(this.className)) return false;
      const mark = RegExp.$1;
      const $target = $(`.${mark}${Config.fadeBodyClassSuffix}`);
      if (!$target.size()) return false;
      $target.css('display') === 'none' ? $target.fadeIn() : $target.fadeOut(); // eslint-disable-line no-unused-expressions
      return false;
    });
    $(`[class*="${Config.fadeBodyClassSuffix}"]`, context).hide();

    //-------------------
    // styleswitch ready
    const $link = $(Config.styleSwitchStyleMark, context);
    if ($link.size()) {
      const styleName = $.cookie('styleName');
      if (styleName) {
        ACMS.Library.switchStyle(styleName, $link);
      }
    }

    //-----------
    // styleswitch
    $(Config.styleSwitchMark, context).click(function () {
      ACMS.Library.switchStyle(this.rel, $(Config.styleSwitchStyleMark));
      return false;
    });

    //------------
    // eval value
    $(Config.inputEvalValueMark, context).each(function () {
      $(this).val(eval($(this).val())); // eslint-disable-line no-eval
    });

    //----------
    // comment
    $(Config.commentCookieMark, context).each(function () {
      if (!$.cookie('acms_comment_name')) return true;
      $('input:text[name=name]', this).val($.cookie('acms_comment_name'));
      $('input:text[name=mail]', this).val($.cookie('acms_comment_mail'));
      $('input:text[name=url]', this).val($.cookie('acms_comment_url'));
      $('input:password[name=pass]', this).val($.cookie('acms_comment_pass'));
      $('input:checkbox[name=persistent]', this).attr('checked', 'checked');
    });
    $(Config.commentCookieUserMark, context).each(function () {
      if (!$.cookie('acms_user_name')) return true;
      let name = $.cookie('acms_user_name');
      let mail = $.cookie('acms_user_mail');
      let url = $.cookie('acms_user_url');
      if (!name) {
        name = '';
      }
      if (!mail) {
        mail = '';
      }
      if (!url) {
        url = '';
      }
      $('input:text[name=name]', this).replaceWith(`<strong>${name}</strong><input type="hidden" name="name" value="${name}" />`);
      $('input:text[name=mail]', this).replaceWith(`<strong>${mail}</strong><input type="hidden" name="mail" value="${mail}" />`);
      $('input:text[name=url]', this).replaceWith(`<strong>${url}</strong><input type="hidden" name="url" value="${url}" />`);
    });

    //-------------
    // ready focus
    $(Config.readyFocusMark, context).focus();

    //--------------
    // ready scroll
    const $elm = $(Config.readyScrollMark, context);
    if ($elm.size()) {
      ACMS.Library.scrollToElm($elm);
    }

    //-----------
    // copyright
    $(Config.copyrightMark, context).click(function () {
      return hs.htmlExpand(this, { // eslint-disable-line no-undef
        objectType: 'iframe',
        wrapperClassName: 'draggable-header',
        headingText: this.title,
        align: 'center',
        width: $(window).width() * 0.5,
        height: $(window).height() * 0.5,
        dimmingOpacity: 0.75,
        dimmingDuration: 25
      });
    });

    // function msie_under8() {
    //   const _ua = ua(); // eslint-disable-line no-undef
    //   if (_ua.ltIE8 || _ua.ltIE7 || _ua.ltIE6) {
    //     return true;
    //   }
    //   return false;
    // }
  };

  ACMS.Dispatch.Utility.getBrowser = getBrowser;
  ACMS.Dispatch.Utility.isOldIE = isOldIE;
  ACMS.Dispatch.Utility.random = random;

  ACMS.Dispatch.Utility.browser = function () {
    const _ua = (function () {
      const browser = ACMS.Dispatch.Utility.getBrowser();
      let IE6 = false;
      let IE7 = false;
      let IE8 = false;
      let IE9 = false;

      if (browser === 'ie9') {
        IE9 = true;
      } else if (browser === 'ie8') {
        IE9 = true; IE8 = true;
      } else if (browser === 'ie7') {
        IE9 = true; IE8 = true; IE7 = true;
      } else if (browser === 'ie6') {
        IE9 = true; IE8 = true; IE7 = true; IE6 = true;
      }

      return {
        ltIE6: IE6,
        ltIE7: IE7,
        ltIE8: IE8,
        ltIE9: IE9,
        mobile: /^(.+iPhone.+AppleWebKit.+Mobile.+|^.+Android.+AppleWebKit.+Mobile.+)$/i.test(navigator.userAgent.toLowerCase()),
        tablet: /^(.+iPad;.+AppleWebKit.+Mobile.+|.+Android.+AppleWebKit.+)$/i.test(navigator.userAgent.toLowerCase())
      };
    }());
    return _ua;
  };

  ACMS.Dispatch.Utility.browser_ie6 = function () {
    const _ua = ACMS.Dispatch.Utility.browser();
    if (_ua.ltIE6) {
      return true;
    }
    return false;
  };

  ACMS.Dispatch.Utility.unloadAlert = (context, selector, force = false) => {
    if (selector) {
      selector = `.js-admin_unload_alert, ${selector}`;
    }

    const $adminForm = $(selector, context);
    if (!$adminForm.length) {
      return false;
    }
    const adminForm = $adminForm.get(0);

    const unload = function () {
      const onBeforeunloadHandler = function (e) {
        e.returnValue = ACMS.i18n('unload.message1');
      };
      window.addEventListener('beforeunload', onBeforeunloadHandler, false);

      if (adminForm) {
        adminForm.addEventListener('submit', () => {
          window.removeEventListener('beforeunload', onBeforeunloadHandler, false);
        });
      }
    };

    if (force) {
      unload();
    } else {
      $adminForm.bind('input', () => {
        unload();
      });
    }
  };
};
