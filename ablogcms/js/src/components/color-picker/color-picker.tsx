import { forwardRef, useCallback, useRef } from 'react';
import { SketchPicker, ColorResult } from '@hello-pangea/color-picker';
import useEffectOnce from '../../hooks/use-effect-once';

interface ColorPickerProps {
  isOpen?: boolean;
  onClose?: () => void;
  defaultColor?: string;
  width?: string;
  colors?: string[];
  onChange?: (color: ColorResult) => void;
  styles?: React.CSSProperties;
}

const ColorPicker = (
  {
    isOpen = false,
    onClose = () => {},
    defaultColor = '#ffffff',
    width = '170px',
    colors = ['#D9E3F0', '#F47373', '#697689', '#37D67A', '#2CCCE4', '#555555', '#dce775', '#ff8a65', '#ba68c8'],
    onChange = () => {},
    styles,
  }: ColorPickerProps,
  ref: React.ForwardedRef<HTMLDivElement>
) => {
  const pickerRef = useRef<HTMLDivElement | null>(null);

  const close = useCallback(() => {
    onClose();
  }, [onClose]);

  const setRefs = useCallback(
    (node: HTMLDivElement | null) => {
      if (ref) {
        (ref as React.MutableRefObject<HTMLDivElement | null>).current = node;
      }
      pickerRef.current = node;
    },
    [ref]
  );

  useEffectOnce(() => {
    const handleClick = (event: MouseEvent) => {
      if (pickerRef.current && event.target instanceof Node && pickerRef.current.contains(event.target)) {
        return;
      }
      close();
    };

    window.addEventListener('click', handleClick);

    return () => {
      window.removeEventListener('click', handleClick);
    };
  });

  const handleChangeComplete = useCallback(
    (color: ColorResult) => {
      onChange(color);
    },
    [onChange]
  );

  if (!isOpen) {
    return null;
  }

  return (
    <div ref={setRefs} style={styles}>
      <SketchPicker color={defaultColor} presetColors={colors} width={width} onChangeComplete={handleChangeComplete} />
    </div>
  );
};

ColorPicker.displayName = 'ColorPicker';
export default forwardRef(ColorPicker);
