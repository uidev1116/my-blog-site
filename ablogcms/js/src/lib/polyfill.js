// reactを動かすのに必要なpolyfill
import 'core-js/features/promise';
import 'core-js/features/object/assign';
import 'core-js/features/symbol';
import 'core-js/features/array/from';
import 'canvas-toBlob';

window.addEventListener('unhandledrejection', () => {
});

window.onunhandledrejection = () => {
};

if (!Element.prototype.matches) {
  Element.prototype.matches = Element.prototype.msMatchesSelector || Element.prototype.webkitMatchesSelector;
}
if (!Element.prototype.closest) {
  Element.prototype.closest = function (value) {
    let element = this;
    do {
      if (element.matches(value)) return element;
      element = element.parentelementement || element.parentNode;
    } while (element !== null && element.nodeType === 1);
    return null;
  };
}

if (window.navigator.userAgent === undefined) {
  window.navigator.userAgent = '';
  window.navigator.appVersion = '';
  window.navigator.platform = '';

  if (window.navigator.userAgentData !== undefined) {
    const uaData = window.navigator.userAgentData;
    const brand = uaData.brands[uaData.brands.length - 1];
    let uaString = `dummy_${uaData.platform}_AppleWebKit_${brand.brand}_xxxx`;
    if (uaData.mobile === true) {
      uaString += '_Mobile_xxxx';
    }
    window.navigator.userAgent = uaString;
    window.navigator.appVersion = brand.version;
    window.navigator.platform = uaData.platform;
  }
}
