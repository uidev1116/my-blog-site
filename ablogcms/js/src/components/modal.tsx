import React, { Component, ReactNode, CSSProperties } from 'react';
import ReactDOM from 'react-dom';
import classnames from 'classnames';
import { hasClass } from '../lib/dom';

interface ModalProp {
  onClose: () => void;
  isOpen: boolean;
  style?: CSSProperties;
  footer?: ReactNode;
  title: ReactNode;
  dialogStyle: CSSProperties;
  dialogClassName?: string;
  header?: ReactNode;
  lastFocus?: boolean;
  noFocus?: boolean;
  className: string;
  tabContentScrollable?: boolean;
}

export default class Modal extends Component<ModalProp> {
  modal: HTMLDivElement | null;

  closeBtn: HTMLButtonElement;

  root: HTMLElement;

  static defaultProps = {
    noFocus: false,
    dialogClassName: 'acms-admin-modal-dialog',
    tabContentScrollable: false,
  };

  constructor(props) {
    super(props);
    this.root = document.createElement('div');
    document.body.appendChild(this.root);
  }

  backdropClicked(e) {
    if (hasClass(e.target, 'acms-admin-modal')) {
      this.props.onClose();
    }
  }

  componentDidUpdate() {
    if (!this.props.noFocus) {
      if (this.props.lastFocus) {
        const buttons = this.modal.querySelectorAll('button');
        if (buttons && buttons.length) {
          buttons[buttons.length - 1].focus();
        }
      } else {
        this.closeBtn.focus();
      }
    }
  }

  componentWillUnmount() {
    document.body.removeChild(this.root);
  }

  render() {
    const {
      children,
      isOpen,
      onClose,
      style,
      footer,
      title,
      dialogStyle,
      header,
      className,
      tabContentScrollable,
      dialogClassName,
    } = this.props;
    const display = isOpen ? 'block' : 'none';

    return ReactDOM.createPortal(
      <div // eslint-disable-line jsx-a11y/click-events-have-key-events, jsx-a11y/no-noninteractive-element-interactions
        className={classnames('acms-admin-modal display', className)}
        style={{ ...style, display }}
        onClick={this.backdropClicked.bind(this)}
        role="dialog"
        ref={(modal) => {
          this.modal = modal;
        }}
      >
        <div className={dialogClassName} style={dialogStyle}>
          <div className="acms-admin-modal-content">
            {!header && (
              <div className="acms-admin-modal-header">
                <button
                  type="button"
                  className="acms-admin-modal-hide acms-admin-icon-delete"
                  onClick={onClose}
                  ref={(closeBtn: HTMLButtonElement) => {
                    this.closeBtn = closeBtn;
                  }}
                  aria-label="閉じる"
                />
                {title}
              </div>
            )}
            {header}
            <div
              className={classnames('acms-admin-modal-body', {
                'acms-admin-modal-body-tab-scrollable': tabContentScrollable,
              })}
            >
              {children}
            </div>
            {footer && <div className="acms-admin-modal-footer">{footer}</div>}
          </div>
        </div>
      </div>,
      this.root,
    );
  }
}
