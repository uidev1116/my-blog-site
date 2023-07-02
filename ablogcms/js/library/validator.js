ACMS.Library.Validator =
  {
    'isFunction': function (val) {
      return ('function' == typeof (ACMS.Library.Validator[val]));
    },
    'required': function (val) {
      return !!val;
    },
    'minlength': function (val, len) {
      if (!val) {
        return true;
      }
      return parseInt(len, 10) <= String(val).length;
    },
    'maxlength': function (val, len) {
      if (!val) {
        return true;
      }
      return parseInt(len, 10) >= String(val).length;
    },
    'min': function (val, num) {
      if (!val) {
        return true;
      }
      return parseInt(num, 10) <= parseInt(val, 10);
    },
    'max': function (val, num) {
      if (!val) {
        return true;
      }
      return parseInt(num, 10) >= parseInt(val, 10);
    },
    'regex': function (val, regex) {
      if (!val) {
        return true;
      }
      var flag = '';
      if (regex.match(/^@(.*)@([igm]*)$/)) {
        regex = RegExp.$1;
        flag = RegExp.$2;
      }

      var re = new RegExp(regex, flag);
      return re.test(val);
    },
    'regexp': function () {
      this.regex(arguments);
    },
    'digits': function (val) {
      if (!val) {
        return true;
      }
      return val == String(parseInt(val, 10));
    },
    'equalTo': function (val, name) {
      return val == $(':input[name="' + name + '"]').val();
    },
    'katakana': function (val) {
      if (!val) {
        return true;
      }
      if (val.match(/^[ァ-ヾー]+$/)) {
        return true;
      }
      return false;
    },
    'hiragana': function (val) {
      if (!val) {
        return true;
      }
      if (val.match(/^[ぁ-ゞー]+$/)) {
        return true;
      }
      return false;
    },
    'all_justChecked': function ($checkbox, num) {
      return parseInt(num, 10) === $checkbox.filter(':checked').length;
    },
    'all_minChecked': function ($checkbox, num) {
      return parseInt(num, 10) <= $checkbox.filter(':checked').length;
    },
    'all_maxChecked': function ($checkbox, num) {
      return parseInt(num, 10) >= $checkbox.filter(':checked').length;
    },
    'dates': function (val) {
      if (!val) {
        return true;
      }
      return /^[sS]{1,2}(\d{2})\W{1}\d{1,2}\W{1}\d{0,2}$|^[hH]{1}(\d{1,2})\W{1}\d{1,2}\W{1}\d{0,2}$|^\d{1,2}$|^\d{1,2}\W{1}\d{1,2}$|^\d{2,4}\W{1}\d{1,2}\W{1}\d{0,2}$|^\d{4}\d{2}\d{2}/.test(val);
    },
    'times': function (val) {
      if (!val) {
        return true;
      }
      return /^\d{1,2}$|^\d{1,2}\W{1}\d{1,2}$|^\d{1,2}\W{1}\d{1,2}\W{1}\d{1,2}$|^\d{2}\d{2}\d{2}/.test(val);
    },
    'url': function (val) {
      if (!val) {
        return true;
      }
      return /^(https?|ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(\#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i.test(val);
    },
    'email': function (val) {
      if (!val) {
        return true;
      }
      return /^(?:(?:(?:(?:[a-zA-Z0-9_!#\$\%&'*+/=?\^`{}~|\-]+)(?:\.(?:[a-zA-Z0-9_!#\$\%&'*+/=?\^`{}~|\-]+))*)|(?:"(?:\\[^\r\n]|[^\\"])*")))\@(?:(?:(?:(?:[a-zA-Z0-9_!#\$\%&'*+/=?\^`{}~|\-]+)(?:\.(?:[a-zA-Z0-9_!#\$\%&'*+/=?\^`{}~|\-]+))*)|(?:\[(?:\\\S|[\x21-\x5a\x5e-\x7e])*\])))$/.test(val);
    },
    'filesize': function (val, max, input) {
      if (!input) {
        return true;
      }
      if (!input.files) {
        return true;
      }
      if (input.files.length < 1) {
        return true;
      }
      if (input.files[0].size > (max * 1024)) {
        return false;
      }
      return true;
    }
  };
