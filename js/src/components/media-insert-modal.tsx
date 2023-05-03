import React, { Component, ReactNode } from 'react';
import queryString from 'qs';
import classnames from 'classnames';

import Modal from './modal';
import MediaList from './media-list';
import MediaModal from './media-modal';
import MediaUploadModal from './media-upload-modal';
import { MediaInsertContainerProp } from '../types/media';
import { RefObject } from '../types';

export default class MediaInsertModal extends Component<MediaInsertContainerProp> {
  static defaultProps = {
    filetype: 'all',
  };

  uploadChild: RefObject<ReactNode>;

  constructor(props) {
    super(props);
    const query = queryString.parse(location.hash.replace('#', ''));
    // e.g http://acms.org/bid/1/admin/media_index/#label=aaa,bbb&upload=true
    if (query.label) {
      props.actions.setLabel(query.label);
    }
    if (query.upload === 'true') {
      props.actions.setUpload(true);
    }
    this.uploadChild = React.createRef();
  }

  componentDidMount() {
    const { props } = this;
    const { actions, tab } = props;

    actions.setFormToken(window.csrfToken);

    if (tab === 'upload') {
      actions.fetchTagList();
      actions.setUpload(true);
    } else {
      actions.setUpload(false);
    }
  }

  insertMedia() {
    const { items, onInsert } = this.props;
    const filters = items.filter((item) => item.checked);
    if (typeof onInsert === 'function') {
      onInsert(filters);
    }
    this.props.actions.setMediaList(items.map((item) => ({ ...item, checked: false })));
  }

  upsert() {
    const { onInsert } = this.props;
    this.uploadChild.current.uploadItems().then((results) => {
      onInsert(results);
    });
  }

  render() {
    const {
      items,
      actions,
      item,
      largeSize,
      formToken,
      archives,
      selectedTags,
      filetype,
      extensions,
      upload,
      label,
      mode,
      lastPage,
      config,
      tags,
      total,
      loading,
      files,
      radioMode,
    } = this.props;

    const footer = upload ? (
      <button type="button" className="acms-admin-btn acms-admin-btn-primary" onClick={this.upsert.bind(this)}>
        {ACMS.i18n('media.upload_insert')}
      </button>
    ) : (
      <button type="button" className="acms-admin-btn acms-admin-btn-primary" onClick={this.insertMedia.bind(this)}>
        {ACMS.i18n('media.insert_selections')}
      </button>
    );

    return (
      <Modal
        noFocus
        tabContentScrollable
        className="acms-admin-media-modal"
        isOpen
        title={<h3 className="acms-admin-modal-heading">{ACMS.i18n('media.media_insert')}</h3>}
        footer={footer}
        dialogStyle={{ maxWidth: '1200px' }}
        dialogClassName="acms-admin-modal-dialog large"
        style={{ backgroundColor: 'rgba(0,0,0,.5)' }}
        onClose={() => {
          this.props.onClose();
        }}
      >
        <div className="acms-admin-tabs" style={{ marginTop: '10px' }}>
          <ul className="acms-admin-tabs-inner">
            <li>
              {/* eslint-disable-next-line jsx-a11y/anchor-is-valid */}
              <a
                href="#"
                className={classnames('js-acms_tab', {
                  'acms-admin-tab-active': !upload,
                })}
                onClick={(e) => {
                  e.preventDefault();
                  actions.setUpload(false);
                }}
              >
                {ACMS.i18n('media.media_list')}
              </a>
            </li>
            <li>
              {/* eslint-disable-next-line jsx-a11y/anchor-is-valid */}
              <a
                href="#"
                className={classnames('js-acms_tab', {
                  'acms-admin-tab-active': upload,
                })}
                onClick={(e) => {
                  e.preventDefault();
                  actions.setUpload(true);
                }}
              >
                {ACMS.i18n('media.upload')}
              </a>
            </li>
          </ul>
          {!upload && (
            <div className="acms-admin-tabs-panel">
              <MediaList
                extensions={extensions}
                filetype={filetype}
                items={items}
                actions={actions}
                mode={mode}
                radioMode={radioMode}
                lastPage={lastPage}
                config={config}
                archives={archives}
                tags={tags}
                total={total}
                loading={loading}
                selectedTags={selectedTags}
              />
            </div>
          )}
          {upload && (
            <div className="acms-admin-tabs-panel">
              <MediaUploadModal
                actions={actions}
                largeSize={largeSize}
                tags={tags}
                label={label}
                config={config}
                ref={this.uploadChild}
                files={files}
                showUploadButton={false}
              />
            </div>
          )}
          {item && <MediaModal item={item} actions={actions} formToken={formToken} config={config} />}
        </div>
      </Modal>
    );
  }
}
