ACMS.Dispatch.Dialog = function (elm) {
  var $elm = $(elm)
  var focusableElements =
    'a[href], area[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), object, embed, *[tabindex], *[contenteditable]'
  $elm
    .parent('.acms-admin-module-edit')
    .show()
    .parent()
    .addClass('acms-admin-module-edit-wrapper')
  $elm.on('click', function (e) {
    e.preventDefault()
    if ($elm.data('open') === true) {
      return
    }
    $elm.data('open', true)
    var href = $elm.attr('href')
    var hash = href.split('#')[1]
    if (hash) {
      location.hash = hash
    }
    $.when(
      fetchHtml(ACMS.Library.acmsLink({ tpl: 'ajax/dialog.html' })),
      fetchHtml(href),
      href,
    ).then(buildModal)
  })
  function fetchHtml(href) {
    var d = new $.Deferred()
    $.get(href, function (html) {
      d.resolve(html)
    })
    return d.promise()
  }
  function buildModal(modal, field, href) {
    var $field = $(field)
    var $modal = $(modal)
    $modal.find('[name="formToken"]').remove()
    $modal
      .find('.js-header')
      .html($field.find(ACMS.Config.dialogTitleMark).html())
    $modal.find('.js-body').html($field.find(ACMS.Config.dialogBodyMark).html())
    $('body').append($modal)
    showModal($modal)
    ACMS.Dispatch($modal)
    ACMS.dispatchEvent('acmsDialogOpened', document, { item: document })
    var $iframe = $('iframe[name="js-ajaxPostData"]', $modal)
    var $form = $('.js-modal-form', $modal)
    $form.attr('action', href)
    $form.on('submit', function () {
      $iframe.off().on('load', function () {
        var res = $iframe.contents(),
          $res = $(res)
        $modal
          .find('.js-body')
          .html($res.find(ACMS.Config.dialogBodyMark).html())
        $('.js-acms_admin_tabs', $modal).removeClass(
          'js-acms_admin_tabs_already',
        )
        $('.js-acms_tab-active', $modal).removeClass('js-acms_tab-active')
        ACMS.Dispatch($modal)
        ACMS.dispatchEvent('acmsDialogOpened', document, { item: document })
        if (
          $modal.find('.acms-admin-alert.acms-admin-alert-danger:visible')
            .length === 0 &&
          $modal.find('label[for^=validator]:visible').length == 0
        ) {
          if (confirm(ACMS.Config.moduleManagementReloadMsg)) {
            window.location.reload()
          } else {
            ACMS.Dispatch.removeSplash()
          }
        } else {
          ACMS.Dispatch.removeSplash()
        }
      })
    })
    ACMS.Dispatch.removeSplash()
  }
  function closeFn($modal, $backdrop) {
    $('body').css('overflow', '')
    $modal.removeClass('display').addClass('out')
    $backdrop.fadeOut()
    setTimeout(function () {
      $modal.remove()
      $elm.data('open', false)
      $elm.focus()
    }, 500)
  }
  function showModal($modal) {
    var $backdrop = $('.acms-admin-modal-backdrop')
    var $hideBtn = $('.acms-admin-modal-hide')
    ACMS.Dispatch.splash()
    if (!$backdrop.length) {
      $backdrop = $(
        $.parseHTML('<div class="acms-admin-modal-backdrop"></div>'),
      )
      $modal.before($backdrop)
    }
    $('body').css('overflow', 'hidden')
    $modal.show()
    $backdrop.show()
    var $first = $modal.find(focusableElements).filter(':visible').first()
    var $last = $modal.find(focusableElements).filter(':visible').last()
    $first.off('keydown.acms-dialog').on('keydown.acms-dialog', function (e) {
      if (e.which === 9 && e.shiftKey) {
        e.preventDefault()
        $last.focus()
      }
    })
    $last.off('keydown.acms-dialog').on('keydown.acms-dialog', function (e) {
      if (e.which === 9 && !e.shiftKey) {
        e.preventDefault()
        $first.focus()
      }
    })
    $first.focus()
    setTimeout(function () {
      $modal
        .removeClass('out')
        .delay(200)
        .queue(function () {
          $(this)
            .addClass('in')
            .delay(500)
            .queue(function () {
              $(this).addClass('display').removeClass('in')
            })
            .dequeue()
        })
    }, 1)
    $hideBtn.on('click', function () {
      closeFn($modal, $backdrop)
    })
    $modal.on('click', function (event) {
      var click = event.target
      if ($(click).hasClass('acms-admin-modal')) {
        closeFn($modal, $backdrop)
      }
    })
  }
}
