import React, { Component } from 'react';
import Draggable, { DraggableData } from 'react-draggable';
import Cropper from 'cropperjs';
import classnames from 'classnames';
import * as FreeStyle from 'free-style';

import DropZone from './drop-zone';
import 'cropperjs/dist/cropper.css';
import 'rc-slider/assets/index.css';
import rotateIcon from '../assets/images/media-rotate-icon.svg';
import focalPointIcon from '../assets/images/media-focal-point.svg';
import cropIcon from '../assets/images/media-crop-icon.svg';
import focalUI from '../assets/images/media-crop-focal.svg';

const Style = FreeStyle.create();
const mediaEditStyle: string = Style.registerStyle({
  display: 'block',
  '.acms-admin-modal-body': {
    backgroundColor: '#1A2029',
    margin: '0',
    padding: '10px 10px 40px 10px',
  },
  '.acms-admin-modal-header': {
    margin: '0',
    h3: {
      margin: '10px 0',
    },
  },
  '.acms-admin-modal-dialog': {
    maxWidth: '1000px',
  },
  '.acms-admin-cropper-wrap': {
    maxWidth: '750px',
    margin: '0 auto',
    boxSizing: 'border-box',
    img: {
      maxWidth: '100%',
    },
  },
  '.acms-admin-cropper-target': {
    width: '100%',
  },
  '.acms-admin-cropper-tools': {
    width: '130px',
    padding: '0 15px',
    boxSizing: 'border-box',
  },
  '.acms-admin-cropper-input-wrap': {
    width: '130px',
    padding: '0 15px',
    boxSizing: 'border-box',
    input: {
      maxWidth: '84px',
    },
  },
  '.acms-admin-cropper-input': {
    backgroundColor: '#2F3641',
    color: '#FFF',
    borderRadius: '3px',
    fontSize: '14px',
    padding: '7px 10px',
    border: 'none',
    'margin-bottom': '5px',
  },
  '.acms-admin-cropper-input-label': {
    fontSize: '10px',
    display: 'inline-block',
    color: '#FFF',
  },
  '.acms-admin-cropper-icon': {
    width: 'auto',
    height: '30px',
    display: 'block',
    margin: '0 auto 5px auto',
  },
  '.acms-admin-cropper-icon-crop': {
    width: '40px',
    height: 'auto',
    display: 'block',
    margin: '0 auto 5px auto',
  },
  '.acms-admin-cropper-reflect-group': {
    display: 'table',
    width: '100%',
    borderRadius: '3px',
    border: '1px solid #333A45',
  },
  '.acms-admin-cropper-reflect-btn': {
    backgroundColor: 'transparent',
    width: '50%',
    display: 'table-cell',
    border: 'none',
    color: '#FFF',
    padding: '10px 15px',
  },
  '.acms-admin-cropper-reflect-icon': {
    width: '20px',
    height: 'auto',
    display: 'block',
    margin: '0 auto 5px auto',
  },
  '.acms-admin-cropper-reflect-label': {
    textAlign: 'center',
    color: '#FFF',
    fontSize: '10px',
    marginBottom: '30px',
  },
  '.acms-admin-cropper-btn-group-wrap': {
    flex: '1',
  },
  '.acms-admin-cropper-btn-group': {
    display: 'table',
  },
  '.acms-admin-cropper-btn-label': {
    display: 'table-cell',
    verticalAlign: 'middle',
    color: '#FFF',
    fontSize: '12px',
    paddingRight: '20px',
  },
  '.acms-admin-cropper-group': {
    display: 'flex',
  },
  '.acms-admin-cropper-btn': {
    borderRadius: '3px',
    backgroundColor: 'transparent',
    color: '#FFF',
    display: 'flex',
    flexDirection: 'column',
    fontSize: '12px',
    border: 'none',
    padding: '10px',
    width: '70px',
    alignItems: 'center',
    verticalAlign: 'middle',
    transition: 'background .3s',
    height: '65px',
    boxSizing: 'border-box',
    justifyContent: 'center',
    '&.active': {
      backgroundColor: '#232B36',
      borderRadius: '3px',
    },
    '&:hover': {
      backgroundColor: '#232B36',
      borderRadius: '3px',
    },
  },
  '.acms-admin-cropper-btn-txt': {
    '@media (max-width: 767px)': {
      display: 'none',
    },
  },
  '.acms-admin-cropper-crop-btn-block': {
    display: 'block',
  },
  '.acms-admin-range-slider': {
    width: '200px',
  },
  '.acms-admin-cropper-img-container': {
    width: '100%',
    height: 'calc(100vh - 385px)',
    overflow: 'hidden',
    margin: '0 auto',
  },
  '.cropper-modal': {
    backgroundColor: '#1A2029',
  },
  '.acms-admin-cropper-focal-point': {
    backgroundImage: `url(${focalUI})`,
    backgroundSize: 'cover',
    backgroundPosition: 'center',
    position: 'absolute',
    width: '50px',
    height: '50px',
    left: '50%',
    top: '50%',
    marginTop: '-25px',
    marginLeft: '-25px',
  },
  '.acms-admin-cropper-bottom': {
    display: 'flex',
    width: '100%',
    alignItems: 'center',
    backgroundColor: '#303945',
    margin: '0',
  },
  '.cropper-bg': {
    background: 'transparent !important',
  },
  '.cropper-face': {
    opacity: '0',
  },
  '.cropper-dashed': {
    border: '1px solid #FFF',
  },
  '.cropper-point': {
    width: '10px',
    height: '10px',
    backgroundColor: '#FFF',
    borderRadius: '50%',
    '&.point-n': {
      marginLeft: '-5px',
      top: '-5px',
    },
    '&.point-e': {
      marginTop: '-5px',
      right: '-5px',
    },
    '&.point-w': {
      left: '-5px',
      marginTop: '-5px',
    },
    '&.point-s': {
      bottom: '-5px',
      marginLeft: '-5px',
    },
    '&.point-ne': {
      right: '-5px',
      top: '-5px',
    },
    '&.point-nw': {
      left: '-5px',
      top: '-5px',
    },
    '&.point-sw': {
      bottom: '-5px',
      left: '-5px',
    },
    '&.point-se': {
      bottom: '-5px',
      right: '-5px',
    },
  },
  '.cropper-line': {
    backgroundColor: '#FFF',
  },
  '.cropper-view-box': {
    outline: '1px solid #FFF',
    outlineColor: '#FFF',
  },
  '.rc-slider-step': {
    backgroundColor: '#e9e9e9',
  },
  '.rc-slider-handle': {
    backgroundColor: '#FFF',
    width: '17px',
    height: '17px',
    border: 'none',
    marginLeft: '-6px',
    marginTop: '-6px',
  },
  '.acms-admin-range-label': {
    fontSize: '12px',
    color: '#FFF',
  },
  '.acms-admin-range-shape-wrap': {
    padding: '0 15px',
    color: '#FFF',
    display: 'inline-block',
    fontSize: '10px',
    verticalAlign: 'middle',
    textAlign: 'center',
    position: 'relative',
  },
  '.acms-admin-range-shape-small': {
    width: '16px',
    height: '16px',
    display: 'block',
    backgroundColor: '#FFF',
    borderRadius: '3px',
  },
  '.acms-admin-range-shape-large': {
    width: '30px',
    height: '30px',
    display: 'block',
    backgroundColor: '#FFF',
    borderRadius: '3px',
  },
  '.acms-admin-range-shape-text': {
    position: 'absolute',
    bottom: '-20px',
    left: '50%',
    marginLeft: '-5px',
  },
  '.acms-admin-cropper-group-wrap': {
    padding: '0 30px',
    '@media (max-width: 767px)': {
      padding: '0',
    },
  },
  '.acms-admin-cropper-crop-list-wrap': {
    position: 'relative',
  },
  '.acms-admin-cropper-crop-list': {
    position: 'absolute',
    bottom: '69px',
    left: '0',
    width: '80px',
    backgroundColor: '#3F4A58',
    textAlign: 'center',
    '&:after': {
      position: 'absolute',
      left: '30px',
      bottom: '-10px',
      content: "' '",
      display: 'block',
      width: '1px',
      height: '0',
      borderStyle: 'solid',
      borderWidth: '10px 10px 0 10px',
      borderColor: '#3f4a58 transparent transparent transparent',
    },
    '@media (max-width: 767px)': {
      width: '120px',
      left: '0px',
      '&:after': {
        left: '50px',
      },
    },
  },
  '.acms-admin-cropper-list-btn': {
    width: '100%',
    border: 'none',
    borderBottom: '1px solid #303945',
    backgroundColor: 'transparent',
    fontSize: '12px',
    padding: '8px 0',
    color: '#FFF',
    '&:hover': {
      backgroundColor: '#232B36',
    },
    '&.active': {
      backgroundColor: '#232B36',
    },
    '&:last-child': {
      borderBottom: 'none',
    },
    '@media (max-width: 767px)': {
      lineHeight: '45px',
      fontSize: '16px',
    },
  },
  '.acms-admin-cropper-crop-info': {
    textAlign: 'center',
    paddingBottom: '13px',
  },
  '.acms-admin-cropper-crop-info-img': {
    verticalAlign: 'middle',
    marginRight: '10px',
    width: '18px',
    height: 'auto',
  },
  '.acms-admin-cropper-crop-info-text': {
    color: '#FFF',
    fontSize: '14px',
    display: 'inline-block',
    verticalAlign: 'middle',
    marginLeft: '5px',
    marginRight: '5px',
  },
  '.acms-admin-cropper-crop-info-input': {
    backgroundColor: '#090C12',
    border: 'none',
    color: '#FFF',
    lineHeight: '22px',
    width: '60px',
    padding: '0 4px',
    display: 'inline-block',
    verticalAlign: 'middle',
    fontSize: '16px',
  },
  '.acms-admin-cropper-original-btn': {
    color: '#006DEC',
    border: 'none',
    background: 'transparent',
    appearance: 'none',
    marginRight: '5px',
    fontSize: '14px',
  },
});

const styleElement = Style.getStyles();

interface MediaEditModalProp {
  onClose: () => void;
  src: string;
  focalPoint: string;
  mediaCropSizes: [number, number][];
  title?: string;
  size: string;
}

interface MediaEditModalState {
  upload: boolean;
  displayWidth?: number;
  displayHeight?: number;
  blob?: Blob | null;
  cropWidth?: number;
  cropHeight?: number;
  useFocalPoint: boolean;
  focalPoint: number[];
  zoom: number;
  ratio: number;
  src: string;
  timeout: NodeJS.Timeout | null;
  isCropListOpen: boolean;
}

export default class MediaEditModal extends Component<MediaEditModalProp, MediaEditModalState> {
  img: HTMLImageElement;

  cropper: Cropper;

  container: HTMLDivElement | null;

  constructor(props) {
    super(props);
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    let focalPoint = [50, 50] as any;
    if (props.focalPoint) {
      focalPoint = props.focalPoint.split(',');
      focalPoint = focalPoint.map((str: string) => parseFloat(str));
    }
    this.state = {
      blob: null,
      upload: false,
      displayWidth: 0,
      displayHeight: 0,
      cropWidth: 0,
      cropHeight: 0,
      zoom: 1,
      ratio: 0,
      src: '',
      useFocalPoint: false,
      focalPoint,
      timeout: null,
      isCropListOpen: false,
    };
  }

  // onComplete(files: ExtendedFile[]) {
  //   resizeImage.getUrlFromFile(files[0], false, 400, false).then((src) => {
  //     this.setState({ src });
  //   });
  // }

  onCancel() {
    this.props.onClose();
  }

  onCrop() {
    let mimeType = 'image/png';
    if (this.img.src.slice(-3) !== 'png') {
      mimeType = 'image/jpeg';
    }
    this.cropper.getCroppedCanvas().toBlob((blob) => {
      const { focalPoint } = this.state;
      this.props.onClose(blob, focalPoint);
    }, mimeType);
  }

  // reset() {
  //   this.cropper.reset();
  // }

  componentDidMount() {
    this.setDisplaySize();
    this.cropper = new Cropper(this.img, {
      autoCrop: false,
      zoomable: false,
      responsive: true,
      viewMode: 2,
      crop: (e) => {
        const data = e.detail;
        if (data.width || data.height) {
          this.setState({
            cropWidth: parseInt(data.width, 10),
            cropHeight: parseInt(data.height, 10),
          });
        } else {
          this.setState({
            cropWidth: 0,
            cropHeight: 0,
          });
        }
      },
      autoCropArea: 1,
      dragMode: 'none',
    });
  }

  setDisplaySize() {
    if (this.img.complete) {
      this.setState({
        displayWidth: this.img.naturalWidth,
        displayHeight: this.img.naturalHeight,
      });
    }
    this.img.onload = () => {
      this.setState({
        displayWidth: this.img.naturalWidth,
        displayHeight: this.img.naturalHeight,
      });
    };
  }

  onUploadImg(files) {
    this.cropper.replace(files[0].preview);
  }

  useOriginalImg() {
    this.cropper.replace(this.props.original);
  }

  changeAspectRatio(ratioProp) {
    const { ratio } = this.state;
    this.cropper.clear();
    if (ratioProp !== ratio) {
      this.cropper.setAspectRatio(ratioProp);
      this.cropper.crop();
      this.setState({
        ratio: ratioProp,
      });
    } else {
      this.setState({
        ratio: 0,
      });
    }
  }

  rotate(rotate) {
    // get data
    const data = this.cropper.getCropBoxData();
    const contData = this.cropper.getContainerData();
    data.width = 2;
    data.height = 2;
    data.top = 0;
    const leftNew = contData.width / 2 - 1;
    data.left = leftNew;
    this.cropper.setCropBoxData(data);
    this.cropper.rotate(rotate);
    const canvData = this.cropper.getCanvasData();
    const heightOld = canvData.height;
    const heightNew = heightOld > contData.height ? contData.height : heightOld;
    const koef = heightNew / heightOld;
    const widthNew = canvData.width * koef;
    canvData.height = heightNew;
    canvData.width = widthNew;
    canvData.top = 0;
    if (canvData.width >= contData.width) {
      canvData.left = 0;
    } else {
      canvData.left = (contData.width - canvData.width) / 2;
    }
    if (canvData.height >= contData.height) {
      canvData.top = 0;
    } else {
      canvData.top = (contData.height - canvData.height) / 2;
    }
    this.cropper.setCanvasData(canvData);
    data.left = 0;
    data.top = 0;
    data.width = canvData.width;
    data.height = canvData.height;
    this.cropper.setCropBoxData(data);
  }

  // zoom(zoom) {
  //   const canvas = this.cropper.getCanvasData();
  //   const container = this.cropper.getContainerData();
  //   const defaultZoomRatio = canvas.naturalWidth / container.width;
  //   this.setState({ zoom }, () => {
  //     this.cropper.zoomTo(zoom / defaultZoomRatio);
  //   });
  // }

  toggleFocalPoint() {
    const { useFocalPoint } = this.state;
    this.setState({
      useFocalPoint: !useFocalPoint,
    });
  }

  focalDragEnd = (_e, data: DraggableData) => {
    const { lastX, lastY } = data;
    const canvas = this.cropper.getCanvasData();
    const { width, height } = canvas;
    const maxWidth = width / 2;
    const maxHeight = height / 2;
    let percentX = ((lastX + maxWidth) * 100) / width;
    let percentY = ((lastY + maxHeight) * 100) / height;
    if (percentX > 100) {
      percentX = 100;
    } else if (percentX < 0) {
      percentX = 0;
    }
    if (percentY > 100) {
      percentY = 100;
    } else if (percentY < 0) {
      percentY = 0;
    }
    this.setState({
      focalPoint: [percentX, percentY],
    });
  };

  getFocalPosition = () => {
    const { focalPoint } = this.state;
    if (!this.container) {
      return null;
    }
    const canvas = this.cropper.getCanvasData();
    if (!canvas) {
      return null;
    }
    const { width, height } = canvas;
    const [percentX, percentY] = focalPoint;
    return {
      x: (width * percentX) / 100 - width / 2,
      y: (height * percentY) / 100 - height / 2,
    };
  };

  hoverOnCropList = () => {
    clearTimeout(this.state.timeout);
    this.setState({
      isCropListOpen: true,
    });
  };

  leaveFromCropList = () => {
    const timeout = setTimeout(() => {
      this.setState({
        isCropListOpen: false,
      });
    }, 500);
    this.setState({
      timeout,
    });
  };

  toggleCropList = (e) => {
    e.preventDefault();
    const { isCropListOpen } = this.state;
    this.setState({
      isCropListOpen: !isCropListOpen,
    });
  };

  getBounds() {
    if (!this.cropper) {
      return;
    }
    const canvData = this.cropper.getCanvasData();
    if (!canvData) {
      return {
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
      };
    }
    const { width, height } = canvData;
    const boundWidth = width / 2;
    const boundHeight = height / 2;
    return {
      top: -boundHeight,
      left: -boundWidth,
      right: boundWidth,
      bottom: boundHeight,
    };
  }

  getImgWidth(size: string) {
    const [width] = size.split(' x ');
    return `${width}px`;
  }

  render() {
    const {
      cropWidth, cropHeight, ratio, useFocalPoint, isCropListOpen,
    } = this.state;
    const {
      src, original, mediaCropSizes, title, size,
    } = this.props;
    const focalPosition = this.getFocalPosition();
    const bounds = this.getBounds();
    return (
      <>
        <style>{styleElement}</style>
        <div
          className={classnames('acms-admin-modal', 'in', mediaEditStyle)}
          style={{ display: 'block', backgroundColor: 'rgb(0,0,0)' }}
        >
          <div className="acms-admin-modal-dialog large">
            <div className="acms-admin-modal-content" style={{ padding: '0' }}>
              <div className="acms-admin-modal-header">
                {/* eslint-disable-next-line */}
                <i className="acms-admin-modal-hide acms-admin-icon-delete" onClick={this.onCancel.bind(this)} />
                <h3>
                  {ACMS.i18n('media.edit_image')}
                  {title ? ` - ${title}` : ''}
                </h3>
              </div>
              <DropZone onComplete={this.onUploadImg.bind(this)}>
                <div className="acms-admin-modal-body" style={{ position: 'relative' }}>
                  <div className="acms-admin-cropper-container">
                    <div
                      className="acms-admin-cropper-crop-info"
                      style={{
                        opacity: cropWidth && cropHeight ? 1 : 0,
                      }}
                    >
                      <img src={cropIcon} className="acms-admin-cropper-crop-info-img" alt="" />
                      <input type="text" className="acms-admin-cropper-crop-info-input" value={cropWidth} readOnly />
                      <span className="acms-admin-cropper-crop-info-text">×</span>
                      <input type="text" className="acms-admin-cropper-crop-info-input" value={cropHeight} readOnly />
                    </div>
                    <div className="acms-admin-cropper-wrap">
                      <div
                        className="acms-admin-cropper-img-container"
                        style={{ position: 'relative', maxWidth: this.getImgWidth(size) }}
                        ref={(container) => {
                          this.container = container;
                        }}
                      >
                        <img
                          src={src}
                          ref={(img: HTMLImageElement) => {
                            this.img = img;
                          }}
                          className="acms-admin-cropper-target"
                          alt=""
                        />
                        {useFocalPoint && (
                          <Draggable bounds={bounds} allowAnyClick onStop={this.focalDragEnd} position={focalPosition}>
                            <div className="acms-admin-cropper-focal-point" />
                          </Draggable>
                        )}
                      </div>
                    </div>
                  </div>
                </div>
              </DropZone>
              <div className="acms-admin-form acms-admin-cropper-bottom">
                <div className="acms-admin-cropper-group-wrap">
                  <div className="acms-admin-cropper-group">
                    <div className="acms-admin-cropper-crop-list-wrap">
                      {/* eslint-disable-next-line jsx-a11y/mouse-events-have-key-events */}
                      <button
                        type="button"
                        className="acms-admin-cropper-btn"
                        onMouseOver={this.hoverOnCropList}
                        onMouseLeave={this.leaveFromCropList}
                        onClick={this.toggleCropList}
                      >
                        <img src={cropIcon} className="acms-admin-cropper-icon" alt="" />
                        <span className="acms-admin-cropper-btn-txt">{ACMS.i18n('media.crop')}</span>
                      </button>
                      {isCropListOpen && (
                        // eslint-disable-next-line jsx-a11y/mouse-events-have-key-events
                        <div
                          className="acms-admin-cropper-crop-list"
                          onMouseOver={this.hoverOnCropList}
                          onMouseLeave={this.leaveFromCropList}
                        >
                          <button
                            type="button"
                            className={classnames('acms-admin-cropper-list-btn', {
                              active: ratio === null,
                            })}
                            onClick={(e) => {
                              e.preventDefault();
                              this.changeAspectRatio(null);
                              this.setState({
                                isCropListOpen: false,
                              });
                            }}
                          >
                            カスタム
                          </button>
                          {mediaCropSizes.map((size) => (
                            <button
                              type="button"
                              className={classnames('acms-admin-cropper-list-btn', {
                                active: ratio === size[0] / size[1],
                              })}
                              onClick={(e) => {
                                e.preventDefault();
                                this.changeAspectRatio(size[0] / size[1]);
                                this.setState({
                                  isCropListOpen: false,
                                });
                              }}
                            >
                              {`${size[0]} / ${size[1]}`}
                            </button>
                          ))}
                        </div>
                      )}
                    </div>
                    <button
                      type="button"
                      className="acms-admin-cropper-btn"
                      onClick={(e) => {
                        e.preventDefault();
                        this.rotate(90);
                      }}
                    >
                      <img src={rotateIcon} className="acms-admin-cropper-icon" alt="" />
                      <span className="acms-admin-cropper-btn-txt">{ACMS.i18n('media.rotate')}</span>
                    </button>
                    <button
                      type="button"
                      className={classnames('acms-admin-cropper-btn', {
                        active: useFocalPoint,
                      })}
                      onClick={(e) => {
                        e.preventDefault();
                        this.toggleFocalPoint();
                      }}
                    >
                      <img src={focalPointIcon} className="acms-admin-cropper-icon" alt="" />
                      <span className="acms-admin-cropper-btn-txt">{ACMS.i18n('media.focal_point')}</span>
                    </button>
                  </div>
                </div>
              </div>
              <div className="acms-admin-modal-footer">
                {original && (
                  <button
                    type="button"
                    className="acms-admin-cropper-original-btn"
                    onClick={this.useOriginalImg.bind(this)}
                  >
                    {ACMS.i18n('media.initialize')}
                  </button>
                )}
                <button type="button" className="acms-admin-btn" onClick={this.onCancel.bind(this)}>
                  {ACMS.i18n('media.cancel')}
                </button>
                <button type="button" className="acms-admin-btn acms-admin-btn-info" onClick={this.onCrop.bind(this)}>
                  {ACMS.i18n('media.apply')}
                </button>
              </div>
            </div>
          </div>
        </div>
      </>
    );
  }
}
