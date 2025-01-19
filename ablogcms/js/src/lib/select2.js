import 'select2/dist/js/select2.full';
import 'select2/dist/css/select2.css';

import { findAncestor } from './dom';

export default (context, option) => {
  $(context)
    .select2(option)
    .on('select2:open', () => {
      const positionY = context.getBoundingClientRect().top;
      let margin = window.innerHeight - positionY;
      if (positionY > margin) {
        margin = positionY;
      }
      margin -= 150;
      if (margin < 200) {
        margin = 200;
      }
      $('.select2-results__options').css('max-height', `${margin}px`);
    })
    .on('select2:select', () => {
      $(context).trigger('change');
      context.dispatchEvent(new Event('change'));
    });
  if (findAncestor(context, '.acms-admin-modal-dialog')) {
    $(context).data('select2').$dropdown.addClass('select2-in-modal');
  }
};
