import * as React from 'react';
import styled from 'styled-components';

const IconListWrap = styled.div`
  position: fixed;
  top: 10px;
  left: 0;
  max-width: 240px;
  z-index: 9999;
  &:before,
  &:after {
    bottom: calc(100% - 1px);
    left: 20px;
    border: solid transparent;
    content: ' ';
    height: 0;
    width: 0;
    position: absolute;
    pointer-events: none;
  }
  &:after {
    border-color: rgba(255, 255, 255, 0);
    border-bottom-color: #fff;
    border-width: 10px;
    margin-left: -10px;
  }
  &:before {
    border-color: rgba(204, 204, 204, 0);
    border-bottom-color: #ccc;
    border-width: 11px;
    margin-left: -11px;
  }
`;

const IconListInner = styled.div`
  border: 1px solid #ccc;
  overflow-y: scroll;
  max-height: 200px;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
`;

const IconList = styled.ul`
  list-style: none;
  margin: 0;
  padding: 10px;
  background: #fff;
  white-space: normal;
  li {
    margin: 0;
    display: inline-block;
  }
`;

const IconButton = styled.button`
  padding: 8px;
  color: #333;
  background-color: #fff;
  font-size: 18px;
  border: 1px solid #fff;
  border-radius: 4px;
  transition: background-color linear 0.15s;
  cursor: pointer;
  &:hover {
    text-decoration: none;
    background: #bae2f3;
  }
`;

interface IconPickerProps {
  icons?: string[];
  defaultValue?: string;
  onChange(icon: string): void;
}

interface IconPickerState {
  icon: string;
  isOpen: boolean;
  top: number;
  left: number;
}

export default class IconPicker extends React.Component<IconPickerProps, IconPickerState> {
  button: HTMLButtonElement | undefined;

  static defaultProps = {
    icons: [],
  };

  constructor(props: IconPickerProps) {
    super(props);
    this.state = {
      icon: props.defaultValue ?? '',
      isOpen: false,
      top: 0,
      left: 0,
    };
  }

  componentDidMount() {
    document.addEventListener('click', this.handleDocumentClick.bind(this));
  }

  componentWillUnmount() {
    document.removeEventListener('click', this.handleDocumentClick.bind(this));
  }

  handleDocumentClick(e: MouseEvent) {
    if (e.target === this.button || e.target === this.button?.children[0]) {
      return;
    }
    this.setState({
      isOpen: false,
    });
  }

  selectIcon(icon: string) {
    this.setState({ icon });
    this.props.onChange(icon);
  }

  openIconList = () => {
    const clientRect = this.button?.getBoundingClientRect();
    if (!clientRect) {
      return;
    }
    const { isOpen } = this.state;
    this.setState({
      isOpen: !isOpen,
      top: clientRect.top,
      left: clientRect.left,
    });
  };

  render() {
    const { icons } = this.props;
    const {
      icon, isOpen, top, left,
    } = this.state;
    return (
      <>
        <div className="acms-admin-btn-group" style={{ padding: '0' }}>
          <button type="button" className="acms-admin-btn" style={{ width: '50px' }}>
            <span className={icon} />
          </button>
          <button
            type="button"
            className="acms-admin-btn"
            onClick={this.openIconList}
            ref={(button) => {
              if (button) {
                this.button = button;
              }
            }}
          >
            <span className="acms-admin-icon-arrow-small-down" />
          </button>
        </div>
        <div style={{ position: 'relative' }}>
          {isOpen && (
            <IconListWrap style={{ top: `${top + 45}px`, left: `${left}px` }}>
              <IconListInner>
                <IconList>
                  {icons?.length
                    && icons.map((icon) => (
                      <li key={icon}>
                        <IconButton
                          type="button"
                          onClick={() => {
                            this.selectIcon(icon);
                          }}
                        >
                          <span className={icon} />
                        </IconButton>
                      </li>
                    ))}
                </IconList>
              </IconListInner>
            </IconListWrap>
          )}
        </div>
      </>
    );
  }
}
