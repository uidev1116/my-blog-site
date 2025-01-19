import { forwardRef } from 'react';
import classNames from 'classnames';

type DraggableButtonProps = Omit<React.ButtonHTMLAttributes<HTMLButtonElement>, 'type' | 'children'>;

const DraggableButton = forwardRef<HTMLButtonElement, DraggableButtonProps>(({ className, ...props }, ref) => (
  <button ref={ref} type="button" className={classNames('acms-admin-btn-draggable', className)} {...props}>
    <span className="acms-admin-icon-sort" aria-hidden />
  </button>
));

DraggableButton.displayName = 'DraggableButton';

export default DraggableButton;
