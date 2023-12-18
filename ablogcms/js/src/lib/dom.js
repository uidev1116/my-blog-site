export const hasClass = (el, className) => {
  if (el.classList) {
    return el.classList.contains(className);
  }
  return new RegExp(`(^| )${className}( |$)`, 'gi').test(el.className);
};

export const addClass = (element, className) => {
  if (element.classList) {
    element.classList.add(className);
  } else {
    element.className += ` ${className}`;
  }
};

export const removeClass = (element, className) => {
  if (element.classList) {
    element.classList.remove(className);
  } else {
    element.className = element.className.replace(new RegExp(`(^|\\b)${className.split(' ').join('|')}(\\b|$)`, 'gi'), ' ');
  }
};

export const wrap = (el, tag) => {
  const parent = document.createElement(tag);
  el.parentElement.insertBefore(parent, el);
  parent.appendChild(el);
  return parent;
};

export const matches = (el, query) => {
  const matched = (el.document || el.ownerDocument).querySelectorAll(query);
  let i = matched.length - 1;
  while (i >= 0 && matched.item(i) !== el) {
    i -= 1;
  }
  return i > -1;
};

export const findAncestor = (el, query) => {
  if (typeof el.closest === 'function') {
    return el.closest(query) || null;
  }
  while (el) {
    if (matches(el, query)) {
      return el;
    }
    el = el.parentElement;
  }
  return null;
};

export const remove = (element) => {
  if (element && element.parentNode) {
    element.parentNode.removeChild(element);
  }
};
