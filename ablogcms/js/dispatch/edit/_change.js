ACMS.Dispatch.Edit._change = function (elm, $unit) {
  var from = parseInt(elm.preValue, 10)
  var to = parseInt($(elm).val(), 10)
  $(ACMS.Config.Edit.itemMark, $unit).each(function () {
    if ('wysiwyg' == $(':input[name^="text_tag_"]', this).val()) {
      var $textarea = $('.entryFormTextarea', this)
      ACMS.Dispatch.wysiwyg.destroy($textarea.get(0))
    }
  })
  if (from == to) {
    return true
  } else if (from < to) {
    var target = null
    var i = $unit.order.length - 1
    for (; i >= 0; i--) {
      if (to >= $unit.order.get(i)) {
        target = i
        break
      }
    }
    var $row = $(elm).parents(ACMS.Config.Edit.itemMark)
    var $target = $unit.find(ACMS.Config.Edit.itemMark).eq(target)
    var pos = parseInt($row.prevAll(ACMS.Config.Edit.itemMark).size(), 10)
    $(':input[name="sort[]"]', $unit).each(function (i) {
      if (i > pos && i <= target) {
        $(this).val($unit.order.get(i - 1))
      }
    })
    if (pos !== target) {
      $row.detach()
      $target.after($row)
      $(elm).val(to)
    }
  } else if (from > to) {
    var target = null
    var i = $unit.order.length - 1
    for (; i >= 0; i--) {
      if (to <= $unit.order.get(i)) {
        target = i
      }
    }
    var $row = $(elm).parents(ACMS.Config.Edit.itemMark)
    var $target = $unit.find(ACMS.Config.Edit.itemMark).eq(target)
    var pos = parseInt($row.prevAll(ACMS.Config.Edit.itemMark).size(), 10)
    $(':input[name="sort[]"]', $unit).each(function (i) {
      if (i < pos && i >= target) {
        $(this).val($unit.order.get(i + 1))
      }
    })
    if (pos !== target) {
      $row.detach()
      $target.before($row)
      $(elm).val(to)
    }
  }
  $(ACMS.Config.Edit.itemMark, $unit).each(function () {
    if ('wysiwyg' == $(':input[name^="text_tag_"]', this).val()) {
      ACMS.Dispatch.wysiwyg.init($('.entryFormTextarea', this).get(0))
    }
  })
  ACMS.Dispatch.Edit._refresh($unit)
  ACMS.Library.scrollToElm($row, { k: 0.3, m: 30 })
}
