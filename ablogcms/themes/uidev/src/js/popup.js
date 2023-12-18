import domContentLoaded from 'dom-content-loaded';
import { addCSSToElement } from './lib/util';

export default () => {
  domContentLoaded(() => {
    // eslint-disable-next-line no-shadow, no-unused-vars
    const popup = (elem) => {
      const elements = typeof elem === 'string' ? document.querySelectorAll(elem) : elem;
      const div = document.createElement('div');
      div.className = 'cover';
      const styles = `
            position: fixed;
            z-index: -1;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            inset: 0;
            cursor: auto;
          `;
      addCSSToElement(div, styles);

      function handleClick() {
        const { id } = this.dataset;
        const { ariaExpanded } = this;
        const target = document.querySelector(id);

        if (ariaExpanded === 'true') {
          target.style.display = 'none';
          this.ariaExpanded = 'false';
          this.removeChild(this.querySelector('.cover'));
        } else {
          const isAllPopupClosed = [].every.call(
            elements,
            ({ ariaExpanded: isOpen }) => isOpen === 'false',
          );
          if (!isAllPopupClosed) return;

          target.style.display = 'block';
          this.ariaExpanded = 'true';
          this.appendChild(div);
        }
      }

      [].forEach.call(elements, (element) => {
        element.addEventListener('click', handleClick);
      });
    };

    popup('.js-popup');
  });
};
