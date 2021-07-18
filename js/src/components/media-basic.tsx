import React, { Component } from 'react';
import queryString from 'qs';
import classnames from 'classnames';

import MediaList from '../components/media-list';
import MediaModal from '../components/media-modal';
import MediaUploadModal from '../components/media-upload-modal';
import { MediaContainerProp } from '../types/media';

export default class MediaBasic extends Component<MediaContainerProp> {
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
  }

  componentDidMount() {
    this.props.actions.setFormToken(window.csrfToken);
    if (this.props.tab === 'upload') {
      this.props.actions.setUpload(true);
    } else {
      this.props.actions.setUpload(false);
    }
    // this.props.actions.fetchMediaList();
  }

  render() {
    const {
      items, actions, item, largeSize, formToken, archives, extensions,
      upload, mode, lastPage, config, tags, total, loading, selectedTags
    } = this.props;

    return (
      <div>
        <div className="acms-admin-tabs">
          <ul className="acms-admin-tabs-inner">
            <li><a
              href="#"
              className={classnames('js-acms_tab', {
                'acms-admin-tab-active': !upload
              })}
              onClick={() => {
                actions.setUpload(false);
              }}

            >{ACMS.i18n("media.media_list")}</a></li>
            <li><a
              href="#"
              className={classnames('js-acms_tab', {
                'acms-admin-tab-active': upload
              })}
              onClick={() => {
                actions.setUpload(true);
              }}
            >{ACMS.i18n("media.upload")}</a></li>
          </ul>
          {!upload && <div className="acms-admin-tabs-panel">
            <MediaList
              items={items}
              actions={actions}
              mode={mode}
              lastPage={lastPage}
              config={config}
              archives={archives}
              tags={tags}
              extensions={extensions}
              total={total}
              loading={loading}
              selectedTags={selectedTags}
            />
          </div>}
          {upload && <div className="acms-admin-tabs-panel">
            <MediaUploadModal actions={actions} largeSize={largeSize} tags={tags}/>
          </div>}
          {item && <MediaModal item={item} actions={actions} formToken={formToken} config={config} tags={tags} />}
        </div>
      </div>
    );
  }
}
