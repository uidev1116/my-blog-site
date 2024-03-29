ACMS.Config.Edit.autoresizeMin = 30
ACMS.Config.Edit.autoresizeMax = 640
ACMS.Dispatch.Edit._item = function (item, $unit) {
  var Edit = this
  $('.removethis', item).click(function () {
    if (confirm(ACMS.i18n('unit.message1'))) {
      Edit._remove(item, $unit)
    }
  })
  if (-1 === navigator.userAgent.indexOf('MSIE 6')) {
    $(':input[name="sort[]"]', item).change(function (e) {
      if (e.originalEvent) {
        Edit._change(this, $unit)
      }
    })
  }
  $(ACMS.Config.resizeImageTargetMarkCF, item).each(function () {
    ACMS.Library.ResizeImage(this)
  })
  $('.js-img_exif_add', item).click(function (e) {
    e.preventDefault()
    var exif = $(this).attr('data-exif')
    $('.js-img_exif_caption', item).val(exif)
  })
  ACMS.Library.ResizeImage(item)
  var toggleIcon = function ($icon, $self) {
    if ($icon.hasClass('acms-admin-icon-expand')) {
      $icon.removeClass('acms-admin-icon-expand')
      $icon.addClass('acms-admin-icon-contract')
      $icon.data('acms-tooltip', ACMS.i18n('unit.message2'))
    } else {
      $icon.removeClass('acms-admin-icon-contract')
      $icon.addClass('acms-admin-icon-expand')
      $icon.data('acms-tooltip', ACMS.i18n('unit.message3'))
    }
    var $clone = $icon.clone()
    $clone.attr('data-acms-tooltip', $icon.data('acms-tooltip'))
    $icon.after($clone)
    $icon.remove()
    setTimeout(function () {
      ACMS.Dispatch($self)
    }, 100)
  }
  $('.togglebody', item).click(function (event) {
    var $itemBody = $(ACMS.Config.Edit.itemBodyMark, item)
    var nextStatus = $itemBody.is(':hidden') ? 'Close' : 'Open'
    var $self = $(this)
    if (event.shiftKey) {
      $(ACMS.Config.Edit.itemMark, $unit).each(function () {
        var $icon = $('.togglebody > i', this)
        var slide = nextStatus == 'Close' ? 'slideDown' : 'slideUp'
        $(ACMS.Config.Edit.itemBodyMark, this)[slide](function () {
          toggleIcon($icon, $self)
        })
      })
    } else {
      var $icon = $('i', this)
      $itemBody.slideToggle('fast', function () {
        toggleIcon($icon, $self)
      })
    }
  })
  var insertAddUnitParts = function () {
    var $tgt = $unit.$target
    $tgt.hide().detach()
    $(item).after($tgt)
    $tgt.fadeIn('fast')
  }
  $(ACMS.Config.Edit.itemHeadMark, item).dblclick(function () {
    insertAddUnitParts()
  })
  $('.js-add-unit-parts', item).click(function () {
    insertAddUnitParts()
  })
  $('.detail-column-head', item).each(function () {
    var elm = this
    $('a.toggle', elm)
      .click(function () {
        $(elm).nextAll('tr').toggle()
        return false
      })
      .click()
  })
  if ($(':input[name^="break_label_"]', item).size()) {
    $(':input[name="align[]"]', item).css('visibility', 'hidden')
    $('.js-align_label', item).css('visibility', 'hidden')
  }
  var browser = ACMS.Dispatch.Utility.browser()
  if (browser.ltIE8) {
    $('textarea[name^="text_text_"]', item).each(function () {
      var $self = $(this)
      $self.width($self.width())
    })
  }
  if (browser.mobile) {
    ACMS.Config.Edit.autoresizeMin = 60
    ACMS.Config.Edit.autoresizeMax = 320
  }
  $('textarea[name^="text_text_"]', item)
    .on('keyup paste', function () {
      var $self = $(this),
        $item = $(item),
        height
      $item.css('height', $item.height() + 'px')
      var browser = ACMS.Dispatch.Utility.browser()
      if (!(browser.ltIE6 || browser.ltIE7 || browser.ltIE8)) {
        $self.height(ACMS.Config.Edit.autoresizeMin)
      }
      $self.attr('scrollHeight')
      height = $self.get(0).scrollHeight
      if (ACMS.Config.Edit.autoresizeMax < height) {
        $self.height(ACMS.Config.Edit.autoresizeMax)
        $self.css('overflow', 'auto')
      } else {
        $self.height(height)
        $self.css('overflow', 'hidden')
      }
      $item.css('height', 'auto')
    })
    .keyup()
  if (!ACMS.Config.LiteEditorFeature) {
    $(':input[name^="text_tag_"]', item)
      .change(function () {
        var $textarea = $('textarea[name^="text_text_"]', item)
        var $type = $(this).val()
        if ($textarea.length > 1) {
          $textarea
            .focus(function () {
              if ('wysiwyg' == $type) {
                removeFocus()
                ACMS.Dispatch.wysiwyg.init(this)
                $('.js-tag_insertion', item).hide()
              }
            })
            .trigger('focus')
          if ('wysiwyg' != $type) {
            removeFocus()
            $textarea.unbind('focus')
          }
        } else {
          if ('wysiwyg' == $type) {
            ACMS.Dispatch.wysiwyg.init($textarea.get(0))
            $('.js-tag_insertion', item).hide()
          } else {
            ACMS.Dispatch.wysiwyg.destroy($textarea.get(0))
            $('.js-tag_insertion', item).show()
          }
        }
        function removeFocus() {
          $textarea.each(function (index, el) {
            ACMS.Dispatch.wysiwyg.destroy(el)
          })
        }
      })
      .change()
  }
  if ($('img.column-map', item).size()) {
    var region = ''
    if (ACMS.Config.s2dRegion) {
      region = '&region=' + ACMS.Config.s2dRegion
    }
    ACMS.Library.googleLoadProxy('maps', '3', {
      callback: function () {
        Edit.map(item)
      },
      options: { region: ACMS.Config.s2dRegion },
    })
  }
  $('.js-acms_layout_select_module', item)
    .unbind('click')
    .bind('click', function () {
      var $unit = $(item),
        $box = $unit.find('.js-acms_layout_contents'),
        $mid = $unit.find('[name^=module_mid_]'),
        $tpl = $unit.find('[name^=module_tpl_]')
      var Dialog = new ACMS.Dispatch.ModuleDialog('index', function (
        res,
        mid,
        tpl,
      ) {
        $box.empty()
        $box.append(res)
        $mid.val(mid)
        $tpl.val(tpl)
        ACMS.Dispatch($box)
      })
      Dialog.show($mid.val(), $tpl.val())
      return false
    })
  $(':input[name="align[]"]', item)
    .change(function () {
      var $closest = $(this).closest('.entryFormColumnItem')
      if (this.value === 'hidden') {
        $closest.addClass('entryFormColumnItem-hidden')
      } else {
        $closest.removeClass('entryFormColumnItem-hidden')
      }
    })
    .change()
  ACMS.dispatchEvent('acmsAddUnit', item, { item: item })
}
