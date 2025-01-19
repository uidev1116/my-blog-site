import { Children, cloneElement, isValidElement, useCallback } from 'react';
import useClipboard, { UseClipboardOptions } from '../../hooks/use-clipboard';

interface CopyToClipboardProps extends UseClipboardOptions {
  children: React.ReactNode;
  text: string;
}

const CopyToClipboard = ({ children, text, ...options }: CopyToClipboardProps) => {
  const { copy } = useClipboard(options);
  const handleClick = useCallback(
    (event: React.MouseEvent) => {
      const element = Children.only(children);
      copy(text);

      // Bypass onClick if it was present
      if (isValidElement(element) && element.props && typeof element.props.onClick === 'function') {
        element.props.onClick(event);
      }
    },
    [copy, children, text]
  );

  const element = Children.only(children);
  if (!isValidElement(element)) {
    return null;
  }

  return cloneElement(element, { ...element.props, onClick: handleClick });
};

export default CopyToClipboard;
