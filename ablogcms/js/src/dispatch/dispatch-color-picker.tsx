import { Suspense, lazy, useCallback, useEffect, useRef, useState } from 'react';
import type { ColorResult } from '@hello-pangea/color-picker';
import { render } from '../utils/react';
import { contrastColor, getOffset } from '../utils';
import useEffectOnce from '../hooks/use-effect-once';

const colors = [
  '#c0c0c0',
  '#ff5555',
  '#f8a102',
  '#ffcc67',
  '#f8ff00',
  '#34ff34',
  '#68cbd0',
  '#34cdf9',
  '#6665cd',
  '#9b9b9b',
  '#cb0000',
  '#f56b00',
  '#ffcb2f',
  '#ffc702',
  '#32cb00',
  '#00d2cb',
  '#3166ff',
  '#6434fc',
  '#656565',
  '#9a0000',
  '#ce6301',
  '#cd9934',
  '#999903',
  '#009901',
  '#329a9d',
  '#3531ff',
  '#6200c9',
  '#343434',
  '#680100',
  '#963400',
  '#986536',
  '#646809',
  '#036400',
  '#34696d',
  '#00009b',
  '#303498',
];

const calcPosition = (button: HTMLButtonElement, picker: HTMLDivElement) => {
  const offset = getOffset(button);
  const windowWidth = window.innerWidth;
  const windowHeight = window.innerHeight;
  const pickerWidth = picker.offsetWidth;
  const pickerHeight = picker.offsetHeight;

  // ボタンの位置に基づいて表示位置を決定するロジック
  const isSpaceOnRight = windowWidth - offset.left > pickerWidth;
  const isSpaceOnLeft = offset.left > pickerWidth;
  const isSpaceBelow = windowHeight - offset.top > pickerHeight;
  const isSpaceAbove = offset.top > pickerHeight;

  if (isSpaceOnRight) {
    // 右側に表示
    return {
      top: `${offset.top}px`,
      left: `${offset.left + button.offsetWidth + 10}px`,
    };
  }
  if (isSpaceOnLeft) {
    // 左側に表示
    return {
      top: `${offset.top}px`,
      left: `${offset.left - 305}px`,
    };
  }
  if (isSpaceBelow) {
    // 下側に表示
    return {
      top: `${offset.top + button.offsetHeight + 10}px`,
      left: `${offset.left}px`,
    };
  }
  if (isSpaceAbove) {
    // 上側に表示
    return {
      top: `${offset.top - 245}px`,
      left: `${offset.left}px`,
    };
  }
  // デフォルトとして右側に表示
  return {
    top: `${offset.top}px`,
    left: `${offset.left + button.offsetWidth + 10}px`,
  };
};

export default function dispatchColorPicker(context: Document | Element) {
  const elements = context.querySelectorAll<HTMLElement>('.js-acms-color-picker');

  if (elements.length === 0) {
    return;
  }
  const navbar = document.querySelector<HTMLElement>('.acms-admin-navbar-admin');
  const fonts = document.querySelectorAll<HTMLElement>(
    '.acms-admin-icon-logo, .acms-admin-navbar-admin-nav > li > a, .acms-admin-navbar-admin-nav > li > button'
  );
  const profileIcons = document.querySelectorAll<HTMLElement>('.acms-admin-user-profile');

  const createDefaultColor = (
    inputs: NodeListOf<HTMLInputElement> | HTMLInputElement | null,
    button: HTMLButtonElement | null
  ) => {
    const input = inputs instanceof NodeList ? inputs[0] : inputs;
    let defaultColor = input?.value || '';
    if (navbar && !defaultColor && button?.dataset.themeColorSelect === 'true') {
      defaultColor = getComputedStyle(navbar).backgroundColor;
    }
    return defaultColor;
  };

  const ColorPicker = lazy(
    () => import(/* webpackChunkName: "color-picker" */ '../components/color-picker/color-picker')
  );

  const LegacyRenderer = ({ element }: { element: HTMLElement }) => {
    const button = element.querySelector<HTMLButtonElement>('.js-acms-color-picker-submit');

    const selector = button?.getAttribute('data-target') || '';
    const input = element.querySelector<HTMLInputElement>(selector);

    const bgColorSelector = button?.getAttribute('data-bgcolor') || '';
    const demo = element.querySelector<HTMLElement>(bgColorSelector);

    const defaultColor = createDefaultColor(input, button);

    const pickerRef = useRef<HTMLDivElement | null>(null);
    const [isOpen, setIsOpen] = useState(false);
    const [position, setPosition] = useState({});

    useEffectOnce(() => {
      const handleClick = (event: MouseEvent) => {
        event.preventDefault();
        event.stopPropagation();
        setIsOpen((prev) => !prev);
      };
      button?.addEventListener('click', handleClick);
      return () => {
        button?.removeEventListener('click', handleClick);
      };
    });

    const handleClose = useCallback(() => {
      setIsOpen(false);
    }, []);

    const handleChange = useCallback(
      (color: ColorResult) => {
        if (demo) {
          demo.style.background = color.hex;
        }
        if (input) {
          input.value = color.hex;
        }

        if (button?.dataset.themeColorSelect === 'true') {
          if (navbar) {
            navbar.style.background = color.hex;
            profileIcons.forEach((profileIcon) => {
              profileIcon.style.border = `2px solid ${contrastColor(color.hex, '#505050')}`;
            });
            fonts.forEach((font) => {
              font.style.color = contrastColor(color.hex, '#505050');
            });
          }
        }
      },
      [button?.dataset.themeColorSelect, input, demo]
    );

    useEffect(() => {
      if (isOpen && button && pickerRef.current) {
        const position = calcPosition(button, pickerRef.current);
        setPosition(position);
      }
    }, [isOpen, button]);

    return (
      <ColorPicker
        ref={pickerRef}
        isOpen={isOpen}
        onClose={handleClose}
        width="225px"
        defaultColor={defaultColor}
        colors={colors}
        styles={{
          position: 'absolute',
          zIndex: 9999,
          transform: 'translateY(-50%)',
          ...position,
        }}
        onChange={handleChange}
      />
    );
  };

  const Renderer = ({ button }: { button: HTMLButtonElement }) => {
    const inputs = context.querySelectorAll<HTMLInputElement>(button.getAttribute('data-target') || '');
    const bgColorsSelector = button.getAttribute('data-bg-color') || '';
    let demos: NodeListOf<HTMLElement> | null = null;
    if (bgColorsSelector) {
      demos = document.querySelectorAll<HTMLElement>(bgColorsSelector);
    }

    const defaultColor = createDefaultColor(inputs, button);

    const pickerRef = useRef<HTMLDivElement | null>(null);
    const [isOpen, setIsOpen] = useState(false);
    const [position, setPosition] = useState({});

    useEffectOnce(() => {
      const handleClick = (event: MouseEvent) => {
        event.preventDefault();
        event.stopPropagation();
        setIsOpen((prev) => !prev);
      };
      button.addEventListener('click', handleClick);
      return () => {
        button.removeEventListener('click', handleClick);
      };
    });

    const handleClose = useCallback(() => {
      setIsOpen(false);
    }, []);

    const handleChange = useCallback(
      (color: ColorResult) => {
        if (demos && demos.length > 0) {
          demos.forEach((demo) => {
            demo.style.background = color.hex;
          });
        }
        if (inputs.length > 0) {
          inputs.forEach((input) => {
            input.value = color.hex;
          });
        }
        if (button.dataset.themeColorSelect === 'true') {
          if (navbar) {
            navbar.style.background = color.hex;
            profileIcons.forEach((profileIcon) => {
              profileIcon.style.border = `2px solid ${contrastColor(color.hex, '#505050')}`;
            });
            fonts.forEach((font) => {
              font.style.color = contrastColor(color.hex, '#505050');
            });
          }
        }
      },
      [button.dataset.themeColorSelect, inputs, demos]
    );

    useEffect(() => {
      if (isOpen && pickerRef.current) {
        const position = calcPosition(button, pickerRef.current);
        setPosition(position);
      }
    }, [isOpen, button]);
    return (
      <ColorPicker
        ref={pickerRef}
        isOpen={isOpen}
        onClose={handleClose}
        width="225px"
        defaultColor={defaultColor}
        colors={colors}
        styles={{
          position: 'absolute',
          transform: 'translateY(-50%)',
          zIndex: 9999,
          ...position,
        }}
        onChange={handleChange}
      />
    );
  };

  elements.forEach((element) => {
    const rootDom = document.createElement('div');
    rootDom.className = 'acms-color-picker';
    document.body.appendChild(rootDom);

    const button = element.querySelector('.js-acms-color-picker-submit');
    if (button !== null) {
      return render(
        <Suspense fallback={null}>
          <LegacyRenderer element={element} />
        </Suspense>,
        rootDom
      );
    }
    render(
      <Suspense fallback={null}>
        <Renderer button={element as HTMLButtonElement} />
      </Suspense>,
      rootDom
    );
  });
}
