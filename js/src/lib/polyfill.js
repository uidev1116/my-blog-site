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
  Element.prototype.closest = function(value) {
    var element = this;
    do {
      if (element.matches(value)) return element;
      element = element.parentelementement || element.parentNode;
    } while (element !== null && element.nodeType === 1);
    return null;
  };
}
