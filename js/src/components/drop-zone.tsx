import React, { Component, ReactNode } from 'react';
import { ExtendedFile } from '../types/media';
import classNames from 'classnames';
import readFiles from '../lib/read-files';

interface DropZoneProp {
  children?: ReactNode,
  onComplete: (files: ExtendedFile[]) => any,
}

interface DropZoneState {
  files: File[],
  modifier: string,
  dragging: number,
  reading: boolean
}

export default class DropZone extends Component<DropZoneProp, DropZoneState> {
  constructor(props) {
    super(props);
    this.state = {
      files: [],
      modifier: 'drag-n-drop-hover',
      dragging: 0,
      reading: false
    };
  }

  componentDidMount() {
    setTimeout(() => {
      this.setState({
        modifier: 'drag-n-drop-hover drag-n-drop-fadeout'
      });
    }, 800);
    setTimeout(() => {
      this.setState({
        modifier: ''
      });
    }, 1100);
  }

  onChange(e) {
    this.setState({
      reading: true
    });
    readFiles(e.target.files).then((files) => {
      this.props.onComplete(files);
    });
  }

  onDrop(e) {
    e.preventDefault();
    e.stopPropagation();
    this.setState({
      modifier: '',
      dragging: 0
    });
    readFiles(e.dataTransfer.files).then((files) => {
      this.props.onComplete(files);
      this.setState({
        reading: false
      })
    });
    return false;
  }

  onDragOver(e) {
    e.preventDefault();
    e.stopPropagation();
    this.setState({
      modifier: 'drag-n-drop-hover'
    });
    return false;
  }

  onDragEnter(e) {
    e.preventDefault();
    e.stopPropagation();
    const { dragging } = this.state;
    this.setState({
      modifier: 'drag-n-drop-hover',
      dragging: dragging + 1
    });
    return false;
  }

  onDragLeave(e) {
    e.preventDefault();
    e.stopPropagation();
    let { dragging } = this.state;
    dragging--;
    if (dragging === 0) {
      this.setState({
        modifier: ''
      });
    }
    this.setState({
      dragging
    });
    return false;
  }

  render() {
    const { children } = this.props;
    const { modifier, reading } = this.state;
    return (
      <div
        className={classNames("acms-admin-media-drop-box-wrap", modifier)}
        onDragOver={this.onDragOver.bind(this)}
        onDragEnter={this.onDragEnter.bind(this)}
        onDragLeave={this.onDragLeave.bind(this)}
        onDrop={this.onDrop.bind(this)}
      >
        { children }
        { !children &&
          <div className={classNames('acms-admin-media', 'acms-admin-media-drop-box', this.state.modifier)}>
            <div className="acms-admin-media-drop-inside">
              <p className="acms-admin-media-drop-text">{ACMS.i18n("media.add_new_media")}</p>
              <label className="acms-admin-media-unit-droparea-btn" style={{ cursor: 'pointer' }}>
                {!reading && <input type="file" multiple name="files[]" onChange={this.onChange.bind(this)} style={{ display: 'none' }} />}
                {ACMS.i18n("media.upload")}
              </label>
              <p className="acms-admin-media-drop-text">{ACMS.i18n("media.drop_file")}</p>
            </div>
          </div>
        }
      </div>
    );
  }
}
