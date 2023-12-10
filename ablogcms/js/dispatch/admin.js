ACMS.Dispatch.Admin = function (context) {
  var Admin = arguments.callee
  if (typeof Config === 'undefined') {
    var Config = ACMS.Config
  }
  if ('config_unit' == ACMS.Config.admin) {
    Admin.Configunit(context)
  }
  $('input#checkAll').change(function () {
    $('input[type=checkbox][name="checks[]"]:not(:disabled):visible').prop(
      'checked',
      this.checked,
    )
  })
  ACMS.Dispatch.Utility.unloadAlert(context)
  $('.adminTable, .acms-admin-table-admin', context)
    .not('.acms-admin-table-admin-edit')
    .each(function () {
      var $tr = $('tbody > tr', this)
      var $theadtd = $('thead td', this)
      $theadtd.hover(
        function () {
          $(this).addClass('hover')
        },
        function () {
          $(this).removeClass('hover')
        },
      )
      $tr.click(function (event) {
        if ($(event.target).find('td').andSelf().filter('td').size()) {
          var $checkbox = $(':checkbox:checked', this)
          if ($checkbox.size()) {
            $checkbox.prop('checked', false)
          } else {
            $('input[name="checks[]"]', this)
              .not(':disabled')
              .prop('checked', 'checked')
          }
        }
      })
    })
  $('.js-incremental_search', context).each(function () {
    Admin.acmsIncrementalSearch(this, $('.js-incremental_search_box', context))
  })
  $('.js-acms_admin_tabs', context).each(function () {
    ACMS.Dispatch.acmsAdminTabs(this)
  })
  $('.js-acms-tab-select', context).each(function () {
    Admin.acmsSelectTabs(this)
  })
  $(
    '.js-acms-dropdown-toggle:not(.js-acms-dropdown-toggle-hover)',
    context,
  ).each(function () {
    Admin.acmsDropdown(this)
  })
  $('.js-acms-dropdown-toggle.js-acms-dropdown-toggle-hover', context).each(
    function () {
      Admin.acmsDropdownHover(this)
    },
  )
  $('table.js-admin_sortable tbody', context).each(function () {
    $('.sorthandle .arrowHandle', this).show()
    $(this).sortable({
      handle: '.sorthandle',
      axis: 'y',
      zIndex: 999,
      opacity: 0.6,
    })
  })
  if ($('.stay').length) {
    var $navi = $('.stay').offset().top - $('.acms-admin-navbar').height()
    var $naviBar =
      $('#sidebar').height() - $('.acms-admin-navbar').height() - 100
    if ($navi > $naviBar) {
      $('#sidebar').scrollTop($navi)
    }
  }
  var $map = $('.js-map-editable', context)
  if ($map.size()) {
    ACMS.Library.googleLoadProxy('maps', '3', {
      callback: function () {
        $map.each(function () {
          ACMS.Dispatch.Edit.map(this)
        })
      },
      options: { region: ACMS.Config.s2dRegion },
    })
  }
  $('table#js-arg_reference .js-arg_reference_trigger', context).click(
    function () {
      var self = arguments.callee,
        $trigger = $(this),
        bid = ACMS.Config.bid,
        field = $trigger.attr('name'),
        url
      if (field === 'session_uid') {
        field = 'uid'
      }
      if ($trigger.attr('data-bid')) {
        bid = $trigger.attr('data-bid')
      }
      if (
        1 &
        (field != 'bid') &
        (field != 'cid') &
        (field != 'ccd') &
        (field != 'eid') &
        (field != 'uid') &
        (field != 'ucd')
      ) {
        return false
      }
      $().dialog()
      $trigger.attr('disabled', 'disabled')
      if ('undefined' == typeof self.$popup) {
        self.$popup = {}
      }
      if (_.contains(['bid', 'uid', 'cid', 'eid'], field)) {
        url = 'ajax/arg/' + field + '-reference.html'
      } else {
        url = 'ajax/arg/reference.html'
      }
      if (0 === $('#reference-' + field, context).length) {
        var url = ACMS.Library.acmsLink(
          { bid: bid, tpl: url, Query: { scope: field, hash: Math.random() } },
          false,
        )
        $.get(url, function (html) {
          var code =
            '                    <div class="acms-admin-modal out">                      <div class="acms-admin-modal-dialog" role="dialog" aria-modal >                        <div class="acms-admin-modal-content">                            <div class="acms-admin-modal-header">                                <button type="button" class="acms-admin-modal-hide">                                  <i class="acms-admin-icon-delete"></i>                                </button>                                <h3 class="acms-admin-modal-heading">ID参照</h3>                            </div>                            <div class="acms-admin-modal-body">                                <div class="acms-admin-margin-top-small js-modal_content clearfix"></div>                            </div>                        </div>                      </div>                    </div>'
          var $backdrop = $(
              $.parseHTML('<div class="acms-admin-modal-backdrop"></div>'),
            )
              .hide()
              .appendTo('body'),
            $modalBox = $($.parseHTML(code)).appendTo('body'),
            $raw = $($.parseHTML(html)),
            $input = $trigger
              .closest('.js-arg_guidance')
              .find('.js-arg_guidance_edit'),
            $labelBox = $trigger
              .closest('.js-arg_guidance')
              .find('.js-arg_guidance_label'),
            $labelDummy = $('.js-arg_reference_dummy')
          if ($modalBox.length) {
            $('body').css('overflow', 'hidden')
            $modalBox.find('.js-modal_content').append($raw)
            setTimeout(function () {
              $modalBox.show()
              $backdrop.show()
              $modalBox
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
              Admin($modalBox)
            }, 200)
          }
          Config.postIncludeMethod = 'replace'
          Config.postIncludeEffect = ''
          Config.postIncludeEffectSpeed = 0
          ACMS.Dispatch.Postinclude.submit($modalBox.find('.js-ajax_load'))
          $('.js-ajax_load_link_delegate', $modalBox).on(
            'click',
            '.js-ajax_load_link a',
            function () {
              var href = $(this).attr('href')
              var query = href.split('?')[1]
              var send = ACMS.Library.getParameterByName('send', '?' + query)
              if (!send) {
                if (!!query) {
                  href += '&send=ajax'
                } else {
                  href += '?send=ajax'
                }
              }
              $.get(href, function (html) {
                $modalBox.find('.js-ajax_load_replace').html(html)
              })
              event.preventDefault()
              return false
            },
          )
          var closeFn = function () {
            $('body').css('overflow', '')
            $backdrop.fadeOut(150, function () {
              $backdrop.remove()
            })
            $modalBox.removeClass('display').addClass('out')
            setTimeout(function () {
              $modalBox.remove()
            }, 500)
            return false
          }
          $('.acms-admin-modal-hide').bind('click', closeFn)
          $modalBox.click(function (event) {
            var click = event.target
            if ($(click).hasClass('acms-admin-modal')) {
              closeFn()
            }
          })
          $('.js-ajax_load_link_delegate', $modalBox).on(
            'click',
            '.js-arg_reference_anchor',
            function () {
              var $label = $labelDummy
                  .clone()
                  .show()
                  .css('display', 'inline-block'),
                ids = $input.val().split(','),
                id = String($(this).data('id')),
                label = $(this).data('label')
              if (!_.include(ids, id)) {
                $label.find('span').text(label)
                $label.attr('data-arg', id)
                Admin.argReferenceRemove($label)
                if ($input.data('multi') === 'on') {
                  $labelBox.append($label[0])
                  $labelBox.append(' ')
                  if (id) ids.push(id)
                } else {
                  $labelBox.html($label[0])
                  if (id) ids = Array(id)
                }
                ids = _.uniq(ids)
                ids = $.grep(ids, function (e) {
                  return e
                })
                $input.val(ids.join())
              }
              closeFn()
              return false
            },
          )
        })
      }
      $trigger.removeAttr('disabled')
      return false
    },
  )
  $('.js-arg_reference_remove', context).each(function () {
    Admin.argReferenceRemove(this)
  })
  $('#js-arg_guidance_name', context).each(function () {
    function setGuidance(module) {
      if (module !== '') {
        var guide = ACMS.Config.Admin.argGuidance[module]
        var axisGuide = ACMS.Config.Admin.axisGuidance[module]
        var multiArgs = ACMS.Config.Admin.multiArgGuidance[module]
        if (typeof guide !== 'undefined') {
          $(
            'input:text, input:checkbox, .js-arg_reference_trigger',
            $('[id^="js-arg_guidance_"]'),
          ).attr('disabled', 'disabled')
          $('.js-arg_reference_toggle', $('[id^="js-arg_guidance_"]')).hide()
          $('tr[id^="js-arg_guidance_"]').css('color', 'silver')
          $.each(guide, function () {
            $('[name^="' + this + '"]').removeAttr('disabled')
            $('.js-target_' + this).show()
            $('tr#js-arg_guidance_' + this).css('color', 'black')
          })
        } else {
          $(
            'input:text, input:checkbox',
            $('[id^="js-arg_guidance_"]'),
          ).removeAttr('disabled')
          $('tr[id^="js-arg_guidance_"]').css('color', 'black')
        }
        if (typeof axisGuide !== 'undefined') {
          $(
            'input:text, input:checkbox, select',
            $('[id^="js-axis_guidance_"]'),
          ).attr('disabled', 'disabled')
          $('tr[id^="js-axis_guidance_"]').css('color', 'silver')
          $.each(axisGuide, function () {
            $('[name^="' + this + '"]').removeAttr('disabled')
            $('tr#js-axis_guidance_' + this).css('color', 'black')
          })
        } else {
          $(
            'input:text, input:checkbox, select',
            $('[id^="js-axis_guidance_"]'),
          ).removeAttr('disabled')
          $('tr[id^="js-axis_guidance_"]').css('color', 'black')
        }
        $('input:text', $('[id^="js-arg_guidance_"]')).removeAttr('data-multi')
        $('.js-arg_guidance_multi_icon', $('[id^="js-arg_guidance_"]')).attr(
          'style',
          'display:none !important',
        )
        if (typeof multiArgs !== 'undefined') {
          $.each(multiArgs, function () {
            $('.js-arg_guidance_multi_icon_' + this).attr(
              'style',
              'display: inline-block !important',
            )
            $('input:text[name^="' + this + '"]').attr('data-multi', 'on')
          })
        }
      }
    }
    $(this).on('change', function () {
      var module = $(this).val()
      setGuidance(module)
    })
    var module = $(this).val()
    setGuidance(module)
  })
  $('.js-arg_reference_toggle ~ .argEdit', context).hide()
  $('.js-arg_reference_toggle', context).click(function () {
    var $item = $(this).closest('.js-arg_guidance')
    var $text = $item.find('.argEdit')
    var $label = $item.find('.argLabel')
    $text.toggle()
    $label.toggle()
    return false
  })
  $('.switching-function-btn:not(:checked)', context).each(function () {
    $(this).closest('table').next('div').hide()
  })
  $('.switching-function-btn', context).click(function () {
    var box = $(this).closest('table').next('div')
    if ($(this).prop('checked') === true) {
      box.slideDown('fast')
    } else {
      box.slideUp('fast')
    }
  })
  $('.js-acms_column_add_type', context).each(function () {
    if ($(this).val() !== '') {
      var attr = $(this).attr('name')
      $('input[type=text][name="' + attr + '"]')
        .attr('disabled', 'disabled')
        .hide()
    }
  })
  $('.js-acms_column_add_type', context).change(function () {
    var attr = $(this).attr('name')
    if ($(this).val() !== '') {
      $('input[type=text][name="' + attr + '"]')
        .attr('disabled', 'disabled')
        .hide()
    } else {
      $('input[type=text][name="' + attr + '"]')
        .removeAttr('disabled')
        .show()
    }
  })
  $('.js-rule_term_type')
    .bind('change', function () {
      var val = $(this).val()
      if (val.length > 0) {
        $(this).siblings('.js-radio_type').val('')
      }
      if (val !== 'fix') {
        $('.js-rule_term_start_date').val('').prop('disabled', true)
        $('.js-rule_term_end_date').val('').prop('disabled', true)
      } else {
        $('.js-rule_term_start_date').prop('disabled', false)
        $('.js-rule_term_end_date').prop('disabled', false)
      }
    })
    .change()
  $('.js-load_hide_box').hide()
  var browser = ACMS.Dispatch.Utility.browser()
  $('#js-select_theme_action').bind(
    browser.ltIE8 ? 'click' : 'change',
    function () {
      var key = $(this).children(':selected').attr('id')
      $('.tplYamlTable').hide()
      $('.js-theme_' + key).show()
    },
  )
  $('#js-index_list_tabs').each(function () {
    var $self = $(this).show(),
      $items = $self.find('a'),
      $tabs = $('.indexListCategory')
    $tabs.not(':first').hide()
    $items.each(function (i) {
      $(this).click(function () {
        $items.removeClass('selected')
        $(this).addClass('selected')
        $tabs.hide()
        $($tabs.get(i)).show()
        $.cookie('lastClickedIndex', i)
        return false
      })
    })
    if ($.cookie('lastClickedIndex') != null) {
      $($items.get($.cookie('lastClickedIndex'))).click()
    } else {
      $items.first().click()
    }
  })
  $('#js-entry_index_category_filter_dup').val(
    $('#js-entry_index_category_filter').val(),
  )
  $('#js-entry_index_category_filter').change(function () {
    $('#js-entry_index_category_filter_dup').val($(this).val())
  })
  $('.formDisable').find(':input').attr('disabled', 'disabled')
  var $inpSubmit = $('.js-submitlock', context)
  if ($inpSubmit.size()) {
    $inpSubmit.click(function () {
      $(this).bind('click', function () {
        alert(ACMS.i18n('submitlock.message1'))
        return false
      })
      return true
    })
  }
  $('.js-admin_action_toggle').click(function () {
    if ($.cookie('acms_config_admin_action_box') == 'on') {
      $('#js-admin_action_box').fadeOut(400)
      $.cookie('acms_config_admin_action_box', '', { path: '/' })
    } else {
      $('#js-admin_action_box').fadeIn(400)
      $.cookie('acms_config_admin_action_box', 'on', { path: '/' })
    }
    return false
  })
  $('.js-acms_entry_index_duplicate').click(function () {
    var $self = $(this)
    var $row = $self.parents('tr')
    var $id = $('[name^="checks"]', $row).val()
    var $form = document.createElement('form')
    var $checks = document.createElement('input')
    var $submit = document.createElement('input')
    var $token = document.createElement('input')
    $checks.name = 'checks[]'
    $checks.value = $id
    $checks.type = 'hidden'
    $submit.name = 'ACMS_POST_Entry_Index_Duplicate'
    $submit.value = 'duplicate'
    $submit.type = 'hidden'
    $token.name = 'formToken'
    $token.type = 'hidden'
    $token.value = window.csrfToken
    $form.method = 'post'
    $form.appendChild($checks)
    $form.appendChild($submit)
    $form.appendChild($token)
    document.body.appendChild($form)
    $form.submit()
    return false
  })
  $('.js-acms_entry_index_delete').click(function () {
    if (confirm(ACMS.i18n('edit.message1'))) {
      var $self = $(this)
      var $row = $self.parents('tr')
      var $id = $('[name^="checks"]', $row).val()
      var $form = document.createElement('form')
      var $checks = document.createElement('input')
      var $submit = document.createElement('input')
      var $token = document.createElement('input')
      $checks.name = 'checks[]'
      $checks.value = $id
      $checks.type = 'hidden'
      $submit.name = 'ACMS_POST_Entry_Index_Trash'
      $submit.value = 'delete'
      $submit.type = 'hidden'
      $token.name = 'formToken'
      $token.type = 'hidden'
      $token.value = window.csrfToken
      $form.method = 'post'
      $form.appendChild($checks)
      $form.appendChild($submit)
      $form.appendChild($token)
      document.body.appendChild($form)
      $form.submit()
    }
    return false
  })
  $('.js-acms_entry_index_trash').click(function () {
    if (confirm(ACMS.i18n('edit.message2'))) {
      var $self = $(this)
      var $row = $self.parents('tr')
      var $id = $('[name^="checks"]', $row).val()
      var $form = document.createElement('form')
      var $checks = document.createElement('input')
      var $submit = document.createElement('input')
      var $token = document.createElement('input')
      $checks.name = 'checks[]'
      $checks.value = $id
      $checks.type = 'hidden'
      $submit.name = 'ACMS_POST_Entry_Index_TrashRestore'
      $submit.value = 'restore'
      $submit.type = 'hidden'
      $token.name = 'formToken'
      $token.type = 'hidden'
      $token.value = window.csrfToken
      $form.method = 'post'
      $form.appendChild($checks)
      $form.appendChild($submit)
      $form.appendChild($token)
      document.body.appendChild($form)
      $form.submit()
    }
    return false
  })
  if ($('.js-acms_fix_marker').length) {
    if (!$('.js-acms_fix_marker').hasClass('js-acms-pretty-fixed')) {
      $('.js-acms_fix_marker').width()
      $('.js-acms_fix_marker').addClass('js-acms-pretty-fixed')
      $('.js-acms_fix_marker').each(function () {
        $(this).parent('.acms-admin-float-right').width($(this).width())
        new ACMS.Library.PrettyScroll($(this).get(0), { offsetTop: 0 })
      })
    }
  }
  $(Config.adminTableSortableMark, context).each(function () {
    ACMS.Dispatch.Admin.adminTableSortable(this)
  })
  $(Config.fieldgroupSortableMarkForm, context).each(function () {
    ACMS.Dispatch.Admin.fieldgroupSortableForm(this)
  })
  $(Config.innerFieldgroupListMark, context).each(function () {
    ACMS.Dispatch.Admin.innerFieldgroupList(this)
  })
  if (
    window.name === 'popup_setting_result' ||
    window.name === 'popup_setting'
  ) {
    $('#acms-admin-main').css({ 'padding-left': '20px', 'padding-top': '5px' })
    $('.acms_fix_bg').css({ 'padding-left': 0, top: 0, left: '10px' })
    $('.acms-admin-navbar-admin, #nav').hide()
    if (window.name === 'popup_setting_result') {
      if (Config.popupSettingConf.autoreload) window.opener.location.reload()
      if (Config.popupSettingConf.autoclose) window.close()
    }
    $('form', context).bind('submit', function () {
      if (window.name === 'popup_setting') window.name = 'popup_setting_result'
    })
  }
}
ACMS.Dispatch.Admin.acmsIncrementalSearch = function (elm, $input) {
  var $item = $('.search_element', elm),
    placeholder = $input.attr('placeholder')
  $input
    .keyup(function () {
      var search = this.value.split(' ')
      if (
        0 ||
        (search.length === 1 && search[0] === placeholder) ||
        (search.length === 2 && search[0] + ' ' + search[1] === placeholder)
      ) {
        return false
      }
      for (var i = 0; i < $item.length; i++) {
        var $clone = $($item[i]).clone(false)
        $clone.find('.except').remove()
        var itemName = $clone.text()
        if (itemName.match(new RegExp(search[0], 'i')) || !search[0]) {
          $($item[i]).show()
          if (search.length > 1) {
            for (var j = 1; j < search.length; j++) {
              if (!itemName.match(new RegExp(search[j], 'i'))) {
                $($item[i]).hide()
              }
            }
          }
        } else {
          $($item[i]).hide()
        }
      }
      return false
    })
    .keyup()
}
ACMS.Dispatch.Admin.acmsSelectTabs = function (elm) {
  var $select = $('.js-acms-tab-select-value', elm)
  var $expr = $('.js-acms-tab-select-panel', elm)
  $select.change(function () {
    $expr.each(function () {
      $(this).removeClass('js-acms-tab-select-active')
    })
    var id = '#' + $(this).val()
    $(id, elm).addClass('js-acms-tab-select-active')
    ACMS.dispatchEvent('acmsAdminSelectTab', elm)
  })
}
ACMS.Dispatch.Admin.acmsDropdown = function (elm) {
  $('.js-acms-dropdown-btn', elm).click(function () {
    var $open = $('.js-acms-dropdown-menu', elm).css('display')
    var within = $('.js-acms-dropdown-menu', elm).data('within')
    var my = $('.js-acms-dropdown-menu', elm).data('pos-my')
    var at = $('.js-acms-dropdown-menu', elm).data('pos-at')
    my = my ? my : 'left top'
    at = at ? at : 'left bottom'
    $('.js-acms-dropdown-menu', document).hide()
    if ($open === 'block') {
      $('.js-acms-dropdown-menu', elm).hide()
    } else {
      var position = { of: this, my: my, at: at }
      if (within) {
        position.within = within
      }
      $('.js-acms-dropdown-menu', elm).show().position(position)
    }
    $(document).unbind('click.dropdonw')
    $(document).bind('click.dropdonw', function () {
      $('.js-acms-dropdown-menu', document).hide()
      $(document).unbind('click.dropdonw')
    })
    return false
  })
}
ACMS.Dispatch.Admin.acmsDropdownHover = function (elm) {
  var $button = $('.js-acms-dropdown-btn', elm)
  var $dropdown = $('.js-acms-dropdown-menu', elm)
  $button.hover(
    function () {
      $dropdown.show()
    },
    function () {
      $dropdown.data(
        'timer',
        setTimeout(function () {
          $dropdown.hide()
        }, 100),
      )
    },
  )
  $dropdown.on('mouseover', function () {
    if ($(this).data('timer')) {
      clearTimeout($(this).data('timer'))
    }
  })
  $dropdown.on('mouseleave', function () {
    $(this).hide()
  })
}
ACMS.Dispatch.Admin.argReferenceRemove = function (elm) {
  $(elm).click(function () {
    var $self = $(this)
    var $input = $self.closest('.js-arg_guidance').find('.argEdit')
    $self.fadeOut(function () {
      var arg = String($self.data('arg'))
      var ids = $input.val().split(',')
      ids = _.reject(ids, function (id) {
        return id === arg
      })
      $input.val(ids.join())
      $(this).remove()
    })
    return false
  })
}
ACMS.Dispatch.Admin.innerFieldgroupList = function (elm) {
  var Config = ACMS.Config
  var $groupList = $(elm)
  var $insert = $groupList.find(Config.innerFieldgroupListInsertMark)
  var $anchor = $groupList.find(Config.innerFieldgroupListTemplateMask)
  var $input = $groupList.find(Config.innerFieldgroupListInputMask)
  $anchor.find(':input').attr('disabled', '')
  var $template = $anchor.clone()
  $anchor.find(':input').attr('disabled', 'disabled')
  $anchor.hide()
  number($groupList)
  if ($insert.size() && $anchor.size()) {
    $insert.unbind('click').click(function () {
      var $clone = $template.clone()
      $clone.removeClass(Config.innerFieldgroupListTemplateMask.substr(1))
      $clone.find(':input').not(Config.innerFieldgroupListDeleteMask).val('')
      $clone.show()
      $clone.find(Config.innerFieldgroupListDeleteMask).click(function () {
        $(this).parents(Config.innerFieldgroupListItemMask).remove()
        number($(this).parents(Config.innerFieldgroupListMark))
        return false
      })
      $anchor.before($clone)
      number($(this).parents(Config.innerFieldgroupListMark))
      ACMS.Dispatch.Admin($clone)
      return false
    })
  }
  $groupList.find(Config.innerFieldgroupListDeleteMask).click(function () {
    $(this).parents(Config.innerFieldgroupListItemMask).remove()
    number($(this).parents(Config.innerFieldgroupListMark))
    return false
  })
  var n = 0
  $groupList.find(Config.fieldgroupListItemMark).each(function () {
    if ('none' == $(this).css('display')) {
      return true
    }
    n++
  })
  if (!n) {
    var $clone = $template.clone()
    $clone.removeClass(Config.innerFieldgroupListTemplateMask.substr(1))
    $(':input[name$="]"]', $clone).each(function () {
      this.name = this.name.replace(/\[\d*\]$/, '[0]')
    })
    if ($anchor.hasClass('new-box')) {
      $anchor.before($clone)
    }
    ACMS.Dispatch.Admin($clone)
  }
  function number($groupList) {
    var $rows = $groupList.find(Config.innerFieldgroupListItemMask + ':visible')
    var n = 0
    $rows.each(function () {
      $(':input[name$="]"]', this).each(function () {
        this.name = n + '-' + this.name
      })
      n++
    })
    n = 0
    $rows.each(function () {
      $(':input[name$="]"]', this).each(function () {
        top_num = this.name.match(/\[\d*\]/)
        this.name = this.name.replace(/\[\d*\]\[\d*\]/, top_num + '[' + n + ']')
      })
      n++
    })
    n = 0
    $rows.each(function () {
      var regex = new RegExp('^' + n + '-')
      $(':input[name$="]"]', this).each(function () {
        this.name = this.name.replace(regex, '')
      })
      n++
    })
  }
}
ACMS.Dispatch.Admin.adminTableSortable = function (elm) {
  if ($(elm).data('sort-enable') !== 'on') {
    $('.item-handle', elm).hide()
    return false
  } else {
    $('.item-handle', elm).show()
  }
  var $adminTable = $('tbody', elm),
    $submitName = $(elm).data('sort-submit'),
    $targetForm = $(elm).parents('form')
  ;($entryOrder = $(elm).data('sort-order')),
    ($isException = $(elm).hasClass('exceptionSort')),
    ($firstSortNum = $('tbody > tr:first', elm).find('.sort-number').val()),
    ($anchor = $(elm).find('.item-template')),
    ($insert = $(elm).find('.item-insert')),
    ($template = $anchor.clone()),
    ($parentList = $(elm).find('[name^=navigation_parent]').eq(1).clone())
  $anchor.find(':input').attr('disabled', 'disabled')
  $anchor.hide()
  $insert.bind('click', function () {
    addItem()
  })
  $adminTable.sortable({
    activate: function (event, ui) {
      var level = ui.item.data('sort-level')
      sortableItem(ui, level)
    },
    deactivate: function (event, ui) {
      $('tr', elm).removeClass('ui-state-disabled')
    },
    update: function (event, ui) {
      var level = ui.item.data('sort-level')
      sortnumber(level)
    },
    handle: '.item-handle',
    items: 'tr:not(.ui-state-disabled)',
  })
  $adminTable.find('.item-delete').click(function () {
    var $item = $(this).parents('.sort-item')
    deleteItem($item)
  })
  function addItem() {
    var $clone = $template.clone(),
      $list = $parentList.clone().val(''),
      count = $(elm).find('[data-sort-level=level-0-0]').length,
      all = $(elm).find('.sort-item').length,
      txt = $clone.html()
    $clone.html(txt.replace(/--seq--/g, all))
    $clone.find('.parent-select').replaceWith($list.get(0))
    var $sortSelect = $clone.find('.sort-select')
    for (var i = 1; i <= count; i++) {
      var $option = $('<option>').val(i).text(i)
      if (i === count) {
        $option.prop('selected', true)
      }
      $sortSelect.append($option)
    }
    $clone.find('.item-delete').click(function () {
      deleteItem($clone)
    })
    $clone.addClass('sort-item').show()
    $anchor.before($clone)
    $adminTable.sortable('refresh')
  }
  function deleteItem($item) {
    var msg = ACMS.Config.fieldgroupSortableItemDeleteMessage
    if (!msg.length || confirm(msg)) {
      $item.removeClass('sort-item').hide()
      $item.find('input:not(:checkbox)').val('')
      $adminTable.sortable('refresh')
    }
  }
  function sortableItem(ui, level) {
    $('tr', elm).each(function () {
      if ($(this).data('sort-level') === level) {
        $(this).removeClass('ui-state-disabled')
      } else {
        $(this).addClass('ui-state-disabled')
      }
    })
    $adminTable.sortable({ items: 'tr:not(.ui-state-disabled)' })
    $adminTable.sortable('refresh')
  }
  function sortnumber(level) {
    var number = 1
    var increment = true
    if ($entryOrder) {
      if ($entryOrder === 'sort-asc') {
        increment = true
      } else if ($entryOrder === 'sort-desc') {
        increment = false
      }
      number = parseInt($firstSortNum)
    }
    $('tr', elm).each(function () {
      if ($(this).data('sort-level') === level) {
        var selctName = $(this).data('sort-name')
        $("[name^='" + selctName + "']", this).val(number)
        if (!$("[name^='" + selctName + "']", this).val()) {
          var $option = $('<option>').val(number).text(number)
          $("[name^='" + selctName + "']", this)
            .append($option)
            .val(number)
        }
        $('[name^=checks]', this).attr('checked', true)
        if (increment) number++
        else number--
      }
    })
    if ($isException) {
      $adminTable.sortable('cancel')
    }
    if ($submitName) {
      $('<input />')
        .attr('type', 'hidden')
        .attr('name', $submitName)
        .attr('value', 'save')
        .appendTo($targetForm)
      $targetForm.submit()
    }
  }
}
ACMS.Dispatch.Admin.fieldgroupSortableForm = function (elm) {
  var Config = ACMS.Config
  var $sortable = $(elm)
  var $insert = $sortable.find(Config.fieldgroupSortableItemInsertMark)
  var $anchor = $sortable.find(Config.fieldgroupSortableItemTemplateMark)
  if ($insert.size() && $anchor.size()) {
    var $template = $anchor.clone()
    $anchor.find(':input').attr('disabled', 'disabled')
    $anchor.hide()
    number($sortable)
    $insert.click(function () {
      var $clone = $template.clone()
      var $addFormUnitTypeLabel = $('#formUnitType option:selected').text()
      var $addFormUnitTypeValue = $('#formUnitType option:selected').val()
      if (
        $addFormUnitTypeValue &&
        $addFormUnitTypeLabel &&
        $clone.find('.form-label')
      ) {
        $clone.find('.form-label').text($addFormUnitTypeLabel)
        $clone.find('input[name*="formbuild_type"]').val($addFormUnitTypeValue)
        switch ($addFormUnitTypeValue) {
          case 'text':
          case 'textarea':
            $clone.find('.select-value-box').hide()
            break
          case 'radio':
          case 'select':
          case 'multiSelect':
          case 'checkbox':
            $clone.find('.textTypeOnly').hide()
            break
          default:
            $clone.find('.inputFormBody').hide()
            break
        }
      }
      $clone.removeClass(Config.fieldgroupSortableItemTemplateMark.substr(1))
      $clone.show()
      $clone.find(Config.fieldgroupSortableItemDeleteMark).click(function () {
        var msg = Config.fieldgroupSortableItemDeleteMessage
        if (!msg.length || confirm(msg)) {
          detouch($sortable)
          $(this).parents(Config.fieldgroupSortableItemMark).remove()
          number($sortable)
          touch($sortable)
        }
        return false
      })
      $anchor.before($clone)
      number($sortable)
      $clone
        .find(Config.fieldgroupSortableItemHandleMark)
        .css('cursor', 'pointer')
      ACMS.Dispatch($clone)
      return false
    })
    var n = 0
    $sortable.find(Config.fieldgroupSortableItemMark).each(function () {
      var $block = $(this)
      if ($block.find('.form-type')) {
        switch ($block.find('.form-type').val()) {
          case 'text':
          case 'textarea':
            $block.find('.select-value-box').hide()
            break
          case 'radio':
          case 'select':
          case 'multiSelect':
          case 'checkbox':
            $block.find('.textTypeOnly').hide()
            break
          default:
            $block.find('.inputFormBody').hide()
            break
        }
      }
      if ('none' == $(this).css('display')) {
        return true
      }
      n++
    })
    if (!n) {
      var $clone = $template.clone()
      $clone.removeClass(Config.fieldgroupSortableItemTemplateMark.substr(1))
      $(':input[name$="]"]', $clone).each(function () {
        this.name = this.name.replace(/\[\d*\]/, '[0]')
      })
      ACMS.Dispatch($clone)
      $anchor.before($clone)
    }
    $sortable.find(Config.fieldgroupSortableItemDeleteMark).click(function () {
      var msg = Config.fieldgroupSortableItemDeleteMessage
      if (!msg.length || confirm(msg)) {
        detouch($sortable)
        $(this).parents(Config.fieldgroupSortableItemMark).remove()
        number($sortable)
        touch($sortable)
      }
      return false
    })
  }
  $sortable
    .find(Config.fieldgroupSortableItemHandleMark)
    .css('cursor', 'pointer')
  $sortable.sortable({
    items: Config.fieldgroupSortableItemMark,
    handle: Config.fieldgroupSortableItemHandleMark,
    axis: 'y',
    start: function (event, ui) {
      detouch($sortable)
    },
    stop: function (event, ui) {
      number($sortable)
      touch($sortable)
      ACMS.Dispatch($sortable)
    },
  })
  function touch(context) {
    $(ACMS.Config.wysiwygMark, context).each(function () {
      ACMS.Dispatch.wysiwyg.init(this)
    })
  }
  function detouch(context) {
    $('textarea', context).each(function () {
      ACMS.Dispatch.wysiwyg.destroy(this)
    })
    return context
  }
  function number($sortable) {
    var $rows = $sortable
      .find(Config.fieldgroupSortableItemMark)
      .not(Config.fieldgroupSortableItemTemplateMark)
    var n = 0
    $rows.each(function () {
      $(':input[name$="]"]', this).each(function () {
        this.name = n + '-' + this.name
      })
      n++
    })
    n = 0
    $rows.each(function () {
      $(':input[name$="]"]', this).each(function () {
        this.name = this.name.replace(/\[\d*\]/, '[' + n + ']')
      })
      n++
    })
    n = 0
    $rows.each(function () {
      var regex = new RegExp('^' + n + '-')
      $(':input[name$="]"]', this).each(function () {
        this.name = this.name.replace(regex, '')
      })
      n++
    })
  }
}
