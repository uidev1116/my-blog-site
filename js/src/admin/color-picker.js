import React from 'react';
import { render } from 'react-dom';
import ColorPicker from '../components/color-picker';
import { wrap, hasClass, findAncestor } from '../lib/dom';
import { contrastColor } from  '../lib/utility';

export default () => {
  const colorPickers = document.querySelectorAll('.js-acms-color-picker');
  const nav = document.querySelector('.acms-admin-navbar-admin');
  const navFont = document.querySelectorAll('.acms-admin-icon-logo, .acms-admin-navbar-admin-nav > li > a, .acms-admin-navbar-admin-nav > li > button');
  const profileIcon = document.querySelector('.acms-admin-user-profile');

  if (colorPickers) {
    [].forEach.call(colorPickers, (colorPicker) => {
      const colorPickerButton = colorPicker.querySelector('.js-acms-color-picker-submit');
      const target = colorPicker.querySelector(colorPickerButton.getAttribute('data-target'));
      const demo = colorPicker.querySelector(colorPickerButton.getAttribute('data-bgcolor'));
      const parent = findAncestor(colorPickerButton, 'span') || wrap(colorPickerButton, 'span');
      let initColor = target.value;
      if (nav && !initColor && hasClass(colorPicker, 'js-acms-theme-select')) {
        initColor = getComputedStyle(nav)['background-color'];
      }
      parent.style.position = 'relative';
      parent.style.display = 'inline-block';
      parent.insertAdjacentHTML('beforeend', "<div class='acms-color-picker'></div>");
      render(
        <ColorPicker
          button={colorPickerButton}
          width="225px"
          defaultColor={initColor}
          colors={[
            '#c0c0c0', '#ff5555', '#f8a102', '#ffcc67', '#f8ff00', '#34ff34', '#68cbd0', '#34cdf9', '#6665cd',
            '#9b9b9b', '#cb0000', '#f56b00', '#ffcb2f', '#ffc702', '#32cb00', '#00d2cb', '#3166ff', '#6434fc',
            '#656565', '#9a0000', '#ce6301', '#cd9934', '#999903', '#009901', '#329a9d', '#3531ff', '#6200c9',
            '#343434', '#680100', '#963400', '#986536', '#646809', '#036400', '#34696d', '#00009b', '#303498'
          ]}
          style={{
            position: 'absolute',
            top: '-200px',
            left: '80px',
            zIndex: 9999
          }}
          handleChangeColor={(color) => {
            if (demo) {
              demo.style.background = color.hex;
            }
            if (target) {
              target.value = color.hex;
            }
            if (hasClass(colorPicker, 'js-acms-theme-select')) {
              if (nav) {
                nav.style.background = color.hex;
                profileIcon.style.border = `2px solid ${contrastColor(color.hex, '#505050')}`;
                [].forEach.call(navFont, (font) => {
                  font.style.color = contrastColor(color.hex, '#505050');
                });
              }
            }
          }}
        />,
        colorPicker.querySelector('.acms-color-picker')
      );
    });
  }
};
