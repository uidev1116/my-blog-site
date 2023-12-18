export default (elm, hover) => {
  const $of = $(elm);

  if (hover !== false) {
    const contents = $of.data('acms-tooltip') || 'ここにヘルプが入ります。';
    const $position = $of.data('acms-position');
    const $tooltip = $($.parseHTML(`<div class="js-tooltip acms-admin-tooltip acms-tooltip">${contents}</div>`));
    let $pos = {
      of: $of,
      at: 'center top',
      my: 'center bottom-10',
    };
    switch ($position) {
      case 'right':
        $pos = {
          of: $of,
          at: 'right center',
          my: 'left+10 center',
        };
        $tooltip.addClass('right');
        break;
      case 'left':
        $pos = {
          of: $of,
          at: 'left center',
          my: 'right-10 center',
        };
        $tooltip.addClass('left');
        break;
      case 'bottom':
        $pos = {
          of: $of,
          at: 'center bottom',
          my: 'center top+10',
        };
        $tooltip.addClass('bottom');
        break;
      case 'top':
        $tooltip.addClass('top');
        break;
      case 'top-left':
        $pos = {
          of: $of,
          at: 'right top',
          my: 'right+8 bottom-10',
        };
        $tooltip.addClass('top-left');
        break;
      case 'top-right':
        $pos = {
          of: $of,
          at: 'left top',
          my: 'left-8 bottom-10',
        };
        $tooltip.addClass('top-right');
        break;
      case 'bottom-left':
        $pos = {
          of: $of,
          at: 'right bottom',
          my: 'right+8 top+10',
        };
        $tooltip.addClass('bottom-left');
        break;
      case 'bottom-right':
        $pos = {
          of: $of,
          at: 'left bottom',
          my: 'left-8 top+10',
        };
        $tooltip.addClass('bottom-right');
        break;
      default:
        break;
    }

    $('.js-tooltip').remove();

    $('body').append($tooltip);
    $tooltip.position($pos).css('visibility', 'visible');
  } else if (hover === false) {
    $('.js-tooltip').remove();
  }

  if (hover === undefined) {
    $(document).unbind('click.tooltip');
    $(document).bind('click.tooltip', () => {
      $('.js-tooltip').remove();
      $(document).unbind('click.tooltip');
    });
  }
};
