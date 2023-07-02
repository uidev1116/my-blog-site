import domContentLoaded from 'dom-content-loaded';
import { getUniqId } from './lib/util';

export default () => {
  domContentLoaded(() => {
    const container = document.querySelector('.js-auto-link-container');
    const headeres = container.querySelectorAll('h2, h3, h4');
    [].forEach.call(headeres, (header) => {
      const id = getUniqId();
      const a = document.createElement('a');
      header.id = id; // eslint-disable-line no-param-reassign
      a.href = `#${id}`;
      a.className = 'c-header-anchor-link';
      header.insertBefore(a, header.firstChild);
    });
  });
};
