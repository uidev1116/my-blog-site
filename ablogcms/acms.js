(function () {
  var ACMS = function () {};
  ACMS.Admin = {};
  ACMS.eventPool = [];

  ACMS.addListener = function (name, listener) {
    var dom = document;
    if (dom.addEventListener) {
      // non-IE
      dom.addEventListener(name, listener, false);
    } else if (dom.attachEvent) {
      // IE8
      dom.documentElement[name] = 0;
      dom.documentElement.attachEvent('onpropertychange', function (event) {
        if (event.propertyName === name) {
          listener(event);
          dom.documentElement.detachEvent('onpropertychange', arguments.callee);
        }
      });
    }
    // dispatch from pool
    var events = ACMS.eventPool[name];
    if (events && events.length > 0) {
      events.forEach(function (item) {
        listener(item.event);
      });
    }
  };

  ACMS.dispatchEvent = function (name, dom, obj) {
    var event;
    dom = dom || document;
    obj = obj || {};

    if (document.createEvent) {
      // non-IE
      event = document.createEvent('HTMLEvents');
      event.obj = obj;
      event.initEvent(name, true, false);
      dom.dispatchEvent(event);
    } else if (dom.createEventObject) {
      // IE
      dom.documentElement[name]++;
    }
    if (!ACMS.eventPool[name]) {
      ACMS.eventPool[name] = [];
    }
    ACMS.eventPool[name].push({
      event: event,
    });
  };

  ACMS.Ready = function (listener) {
    ACMS.addListener('acmsReady', listener);
  };
  ACMS.Loaded = function (listener) {
    if (document.readyState === 'complete') {
      listener();
    } else {
      window.addEventListener('load', listener);
    }
  };
  window.ACMS = ACMS;

  // init tab
  (function ready(fn) {
    if (document.attachEvent ? document.readyState === 'complete' : document.readyState !== 'loading') {
      fn();
    } else {
      document.addEventListener('DOMContentLoaded', fn);
    }
  })(function () {
    if (document.getElementById('custom-field-maker')) {
      return;
    }
    [].forEach.call(document.querySelectorAll('.acms-admin-tabs'), function (tabs) {
      [].forEach.call(tabs.querySelectorAll('.acms-admin-tabs-panel'), function (panel) {
        panel.style.display = 'none';
      });
    });
  });
})();
